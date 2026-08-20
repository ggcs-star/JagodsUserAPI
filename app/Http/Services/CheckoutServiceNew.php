<?php

namespace App\Http\Services;

use App\Models\Order;
use App\Models\OrderLineItem;
use App\Models\Discount;
use App\Models\Coupon;
use App\Models\Setting;
use App\Enums\DiscountStatus;
use App\Enums\OrderTypeStatus;
use App\Enums\Module;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\OrderStatus;
use App\Models\UserDevice;
use App\Libraries\MyString;
use App\Models\OrderHistory;

class CheckoutServiceNew
{
    protected $validationService;
    protected $shiprocketService;

    public function __construct(
        CheckoutValidationService $validationService,
        ShiprocketService $shiprocketService
    ) {
        $this->validationService = $validationService;
        $this->shiprocketService = $shiprocketService;
    }

    public function checkout(array $data, int $userId, $device = null)
    {
        return DB::transaction(function () use ($data, $userId, $device) {

            $validated = $this->validationService->validate(
                $data,
                $userId
            );

            $orderPricing = $this->calculateOrderPricing(
                $validated,
                $data,
                $userId
            );

            $frontendTotal = (float) $data['total'];

            if (
                abs(
                    $orderPricing['total'] - $frontendTotal
                ) > 0.01
            ) {
                throw new Exception(json_encode([
                    'error_type' => 'total_mismatch',
                    'message' => "Order total has changed. You sent ₹{$frontendTotal}, but current payable amount is ₹{$orderPricing['total']}. Please review your cart.",
                    'old_total' => $frontendTotal,
                    'current_total' => $orderPricing['total'],
                ]), 422);
            }

            $order = $this->createOrder(
                $data,
                $validated,
                $orderPricing,
                $userId,
                $device
            );

            $this->createOrderHistory($order);

            if ($orderPricing['coupon_id']) {
                $this->createDiscountRecord(
                    $order,
                    $orderPricing,
                    $userId
                );
            }

            $this->createOrderItems(
                $order,
                $validated['items']
            );

            return $order->fresh([]);
        });
    }

    private function calculateOrderPricing(
        array $validated,
        array $data,
        int $userId
    ): array {

        $subtotal = $validated['subtotal'];

        $settings = Setting::pluck('value', 'key');

        $couponId = null;
        $couponDiscount = 0;

        if (!empty($data['coupon_code'])) {

            $coupon = Coupon::whereRaw(
                'BINARY slug = ?',
                [$data['coupon_code']]
            )
                ->where('from_date', '<=', now())
                ->where('to_date', '>=', now())
                ->where('limit', '>', 0)
                ->where(function ($query) use ($data) {
                    $query
                        ->where(
                            'restaurant_id',
                            $data['restaurant_id']
                        )
                        ->orWhere('restaurant_id', 0);
                })
                ->first();

            if (!$coupon) {
                throw new Exception(
                    'This coupon is invalid or expired.',
                    422
                );
            }

            if (
                $coupon->minimum_order_amount > 0 &&
                $subtotal < $coupon->minimum_order_amount
            ) {
                throw new Exception(
                    'This coupon requires a minimum order amount of ₹' .
                    $coupon->minimum_order_amount,
                    422
                );
            }

            if (
                Discount::where('coupon_id', $coupon->id)
                    ->where('status', DiscountStatus::ACTIVE)
                    ->count() >= $coupon->limit
            ) {
                throw new Exception(
                    'This coupon is fully redeemed and no longer available.',
                    422
                );
            }

            $couponId = $coupon->id;

            $couponDiscount = (
                $coupon->discount_type === 'percent'
            )
                ? ($subtotal * $coupon->amount) / 100
                : $coupon->amount;

            $couponDiscount = min(
                $couponDiscount,
                $subtotal
            );
        }

        $taxableAmount = max(
            0,
            $subtotal - $couponDiscount
        );

        $gstAmount = ($taxableAmount * 5) / 100;

        $packagingCharge = (float) (
            $settings['packaging_charge'] ?? 0
        );

        $platformFee = $subtotal > 0
            ? (float) ($settings['platform_fee'] ?? 0)
            : 0;

        $moduleId = (int) $data['module_id'];

        $isPickup = (
            (int) $data['order_type'] === OrderTypeStatus::PICKUP
        );


        $surgeFee = 0;

        if (
            !$isPickup &&
            $moduleId === Module::YOUR_CITY
        ) {
            $surgeFee = $subtotal > 0
                ? (float) ($settings['surge_fee'] ?? 0)
                : 0;
        }


        $deliveryCharge = $this->calculateDeliveryCharge(
            $data,
            $settings,
            $validated['restaurant'],
            $validated['address'],
            $validated['items']
        );

        $tipAmount = (float) (
            $data['tip_amount'] ?? 0
        );

        $total = max(
            0,
            $taxableAmount
            + $gstAmount
            + $deliveryCharge
            + $packagingCharge
            + $platformFee
            + $surgeFee
            + $tipAmount
        );

        return [
            'subtotal' => $subtotal,
            'product_discount' => $validated['product_discount'],
            'coupon_id' => $couponId,
            'discount' => $couponDiscount,
            'gst_amount' => $gstAmount,
            'delivery_charge' => $deliveryCharge,
            'packaging_charge' => $packagingCharge,
            'platform_fee' => $platformFee,
            'surge_fee' => $surgeFee,
            'large_order_fee' => 0,
            'tip_amount' => $tipAmount,
            'total' => round($total, 2),
        ];
    }

    private function calculateDeliveryCharge(
        array $data,
        $settings,
        $restaurant,
        $address,
        array $items
    ): float {

        $isPickup = (
            (int) $data['order_type'] === OrderTypeStatus::PICKUP
        );

        $moduleId = (int) $data['module_id'];

        if ($isPickup) {
            return 0;
        }

        if ($moduleId === Module::ALL_OVER_INDIA) {

            if (!$address) {
                throw new Exception(
                    'Delivery address is required.',
                    422
                );
            }

            if (blank($address->pincode)) {
                throw new Exception(
                    'Delivery pincode is required.',
                    422
                );
            }

            $totalQuantity = array_sum(
                array_column($items, 'quantity')
            );

            if ($totalQuantity <= 0) {
                throw new Exception(
                    'Invalid order quantity.',
                    422
                );
            }


            $weight = $totalQuantity * 1;

            $cod = (
                (int) $data['payment_method']
                === PaymentMethod::CASH_ON_DELIVERY
            )
                ? 1
                : 0;

            $delivery = $this->shiprocketService->getDeliveryRate(
                deliveryPincode: (string) $address->pincode,
                weight: (float) $weight,
                cod: $cod
            );

            if (
                !data_get(
                    $delivery,
                    'available',
                    false
                )
            ) {
                throw new Exception(
                    data_get(
                        $delivery,
                        'message',
                        'Delivery is not available for this pincode.'
                    ),
                    422
                );
            }

            return round(
                (float) data_get(
                    $delivery,
                    'courier.total_charge',
                    0
                ),
                2
            );
        }

        if ($moduleId === Module::YOUR_CITY) {

            $basicDeliveryCharge = (float) (
                $settings['basic_delivery_charge'] ?? 0
            );

            if (
                !$restaurant ||
                !$address ||
                $restaurant->lat === null ||
                $restaurant->long === null ||
                $address->latitude === null ||
                $address->longitude === null
            ) {
                return round(
                    $basicDeliveryCharge,
                    2
                );
            }

            $distance = $this->calculateDistance(
                (float) $address->latitude,
                (float) $address->longitude,
                (float) $restaurant->lat,
                (float) $restaurant->long
            );

            $maxDeliveryRadius = (float) (
                $settings['max_delivery_radius'] ?? 0
            );

            if (
                $maxDeliveryRadius > 0 &&
                $distance > $maxDeliveryRadius
            ) {
                throw new Exception(
                    "Sorry! This restaurant does not deliver to your location. " .
                    "Maximum delivery radius is {$maxDeliveryRadius} km, " .
                    "but you are {$distance} km away.",
                    422
                );
            }

            $freeDeliveryRadius = (float) (
                $settings['free_delivery_radius'] ?? 0
            );

            $chargePerKm = (float) (
                $settings['charge_per_kilo'] ?? 0
            );

            $chargeableDistance = max(
                0,
                $distance - $freeDeliveryRadius
            );


            $distanceCharge =
                $chargeableDistance * $chargePerKm;


            return round(
                $basicDeliveryCharge + $distanceCharge,
                2
            );
        }

        return 0;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return round($earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a))), 2);
    }

    private function createOrder(array $data, array $validated, array $pricing, int $userId, ?UserDevice $device): Order
    {
        $address = $validated['address'];
        $latitude = $address ? $address->latitude : 0.0;
        $longitude = $address ? $address->longitude : 0.0;

        $order = Order::create([
            'user_id' => $userId,
            'user_device_id' => $device ? $device->id : null,
            'restaurant_id' => $data['restaurant_id'],
            'address_id' => $data['address_id'] ?? null,
            'coupon_id' => $pricing['coupon_id'],
            'order_type' => $data['order_type'],
            'payment_method' => $data['payment_method'],
            'payment_status' => PaymentStatus::UNPAID,
            'status' => $data['payment_method'] == PaymentMethod::CASH_ON_DELIVERY ? OrderStatus::PENDING : OrderStatus::PAYMENT_PENDING,

            'address' => $address ? json_encode([
                'address' => $address->address,
                'apartment' => $address->apartment,
                'pincode' => $address->pincode,
            ]) : '',

            'lat' => $latitude,
            'long' => $longitude,
            'mobile' => auth()->user()->phone ?? '',

            'sub_total' => $pricing['subtotal'],
            'product_discount' => $pricing['product_discount'],
            'discount' => $pricing['discount'],
            'gst_amount' => $pricing['gst_amount'],
            'delivery_charge' => $pricing['delivery_charge'],
            'packing_charge' => $pricing['packaging_charge'], // Ensure this matches DB column name (packing_charge or packaging_charge)
            'platform_fee' => $pricing['platform_fee'],
            'large_order_fee' => $pricing['large_order_fee'],
            'surge_fee' => $pricing['surge_fee'],
            'tip_amount' => $pricing['tip_amount'],
            'total' => $pricing['total'],
            'order_instructions' => $data['order_instructions'] ?? null,
        ]);

        $order->misc = json_encode([
            'order_code' => 'ORD-' . MyString::code($order->id),
            'remarks' => $data['order_instructions'] ?? '',
        ]);
        $order->save();

        return $order;
    }

    private function createOrderHistory(Order $order): void
    {
        OrderHistory::create([
            'order_id' => $order->id,
            'previous_status' => null,
            'current_status' => $order->status,
        ]);
    }

    private function createDiscountRecord(Order $order, array $pricing, int $userId): void
    {
        Discount::create([
            'order_id' => $order->id,
            'coupon_id' => $pricing['coupon_id'],
            'user_id' => $userId,
            'amount' => $pricing['discount'],
            'status' => DiscountStatus::ACTIVE,
        ]);
    }

    private function createOrderItems(Order $order, array $validatedItems): void
    {
        $orderItems = [];
        foreach ($validatedItems as $item) {
            $orderItems[] = [
                'order_id' => $order->id,
                'restaurant_id' => $order->restaurant_id,
                'menu_item_id' => $item['menu_item_id'],
                'menu_item_variation_id' => $item['variation_id'],
                'unit_price' => $item['unit_price'],
                'discounted_price' => $item['discount_price'],
                'quantity' => $item['quantity'],
                'item_total' => $item['item_total'],
                'options' => json_encode($item['options']),
                'options_total' => $item['options_total'],
                'instructions' => $item['instructions'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        OrderLineItem::insert($orderItems);
    }
}