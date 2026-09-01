<?php

namespace App\Http\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Discount;
use App\Models\CartItem;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\MenuItemOption;
use App\Models\MenuItemVariation;
use App\Models\Setting;
use App\Enums\CouponType;
use App\Enums\DiscountStatus;
use App\Enums\OrderTypeStatus;
use Exception;
use App\Models\Address;
use App\Enums\Module;
use Illuminate\Support\Facades\DB;
use App\Enums\MenuItemStatus;
class CartService
{
    public function getCart($userId)
    {
        $cart = Cart::with(['coupon', 'items.menuItem', 'items.variation'])
            ->where('user_id', $userId)
            ->first();

        if ($cart) {
            $this->syncCartItems($cart);
            $this->updateCartTotals($cart);
            return $cart->fresh(['coupon', 'items.menuItem', 'items.variation']);
        }

        return null;
    }

    public function addToCart(array $data, $userId)
    {
        $menuItem = MenuItem::with('module')->find($data['menu_id']);

        if (!$menuItem) {
            throw new Exception('Menu item not found', 404);
        }
        $existingCart = Cart::with('items.menuItem.module')
            ->where('user_id', $userId)
            ->first();

        if ($existingCart && $existingCart->items->isNotEmpty()) {

            $existingItem = $existingCart->items->first();

            $existingModule = optional($existingItem->menuItem->module)->slug;
            $newModule = optional($menuItem->module)->slug;

            if (
                $existingModule &&
                $newModule &&
                $existingModule !== $newModule
            ) {

                throw new Exception(
                    json_encode([
                        'message' => 'Your cart already contains items from "' .
                            ucfirst(str_replace('_', ' ', $existingModule)) .
                            '". Please clear your cart before adding items from "' .
                            ucfirst(str_replace('_', ' ', $newModule)) .
                            '".',

                        'current_module' => $existingModule,
                        'current_module_name' => ucfirst(str_replace('_', ' ', $existingModule)),
                        'new_module' => $newModule,
                        'new_module_name' => ucfirst(str_replace('_', ' ', $newModule)),
                    ]),
                    422
                );
            }
        }
        if (isset($data['address_id'])) {
            $address = Address::find($data['address_id']);
            if ($address) {
                $this->checkDeliveryRadius($menuItem->restaurant_id, $address->latitude, $address->longitude, OrderTypeStatus::DELIVERY);
            }
        } elseif (isset($data['latitude']) && isset($data['longitude'])) {
            $this->checkDeliveryRadius($menuItem->restaurant_id, $data['latitude'], $data['longitude'], OrderTypeStatus::DELIVERY);
        }

        $cart = $this->firstOrCreateCart(
            $userId,
            $menuItem->restaurant_id,
            ['address_id' => $data['address_id'] ?? null]
        );

        $cart->update([
            'restaurant_id' => $menuItem->restaurant_id,
            'address_id' => $data['address_id'] ?? $cart->address_id,
            'order_instructions' => $data['order_instructions'] ?? $cart->order_instructions,
        ]);

        // ✅ Step 1 changes implemented in calculateItemPrice
        $priceDetails = $this->calculateItemPrice(
            $menuItem,
            $data['variation_id'] ?? null,
            $data['options'] ?? []
        );

        $requestedQty = $data['quantity'] ?? 1;

        if ($requestedQty > $menuItem->max_cart_quantity) {
            throw new Exception(
                "Maximum allowed quantity is {$menuItem->max_cart_quantity}",
                422
            );
        }

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('menu_item_id', $menuItem->id)
            ->where('variation_id', $priceDetails['variation_id'])
            ->first();

        if ($requestedQty == 0) {
            if ($existingItem) {
                $existingItem->delete();
            }
            $this->updateCartTotals($cart);
            return $cart->fresh([
                'items'
            ]);
        }

        // ✅ Step 2: total_price calculation update
        $totalPrice = $priceDetails['final_price'] * $requestedQty;

        if ($existingItem) {
            $existingItem->update([
                'menu_name' => $menuItem->name,
                'menu_slug' => $menuItem->slug,
                'menu_image' => $menuItem->image,
                'variation_name' => $priceDetails['variation_name'] ?? null,
                // ✅ Step 2: Update array assignments
                'unit_price' => $priceDetails['unit_price'],
                'discount_price' => $priceDetails['discount_price'],
                'final_price' => $priceDetails['final_price'],
                'total_price' => $totalPrice,
                'options' => $priceDetails['options'],
                'instructions' => $data['instructions'] ?? null,
                'quantity' => $requestedQty,
                'is_available' => true,
                'is_price_changed' => false,
            ]);

        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'menu_item_id' => $menuItem->id,
                'variation_id' => $priceDetails['variation_id'],
                'menu_name' => $menuItem->name,
                'menu_slug' => $menuItem->slug,
                'menu_image' => $menuItem->image,
                'variation_name' => $priceDetails['variation_name'] ?? null,
                // ✅ Step 2: Insert array assignments
                'unit_price' => $priceDetails['unit_price'],
                'discount_price' => $priceDetails['discount_price'],
                'final_price' => $priceDetails['final_price'],
                'total_price' => $totalPrice,
                'options' => $priceDetails['options'],
                'instructions' => $data['instructions'] ?? null,
                'quantity' => $requestedQty,
                'is_available' => true,
                'is_price_changed' => false,
            ]);
        }

        $this->updateCartTotals($cart);

        return $cart->fresh([
            'items'
        ]);
    }

    public function updateCartDetails(array $data, $userId)
    {
        $cart = $this->getCartOrFail($userId);
        $cart->loadMissing('items.menuItem.module');

        $firstItem = $cart->items->first();

        $moduleSlug = optional($firstItem?->menuItem?->module)->slug;

        if (
            isset($data['order_type']) &&
            $data['order_type'] == OrderTypeStatus::PICKUP &&
            $moduleSlug == Module::ALL_OVER_INDIA_SLUG
        ) {
            throw new Exception(
                'Pickup is not available for All Over India orders.',
                422
            );
        }
        $updateData = [];
        $latToCheck = null;
        $lngToCheck = null;

        if (isset($data['address_id'])) {
            $updateData['address_id'] = $data['address_id'];
            $address = Address::find($data['address_id']);
            if ($address) {
                $latToCheck = $address->latitude;
                $lngToCheck = $address->longitude;
            }
        } else if (isset($data['latitude']) && isset($data['longitude'])) {
            $updateData['latitude'] = $data['latitude'];
            $updateData['longitude'] = $data['longitude'];
            $latToCheck = $data['latitude'];
            $lngToCheck = $data['longitude'];
        }

        $currentOrderType = $data['order_type'] ?? $cart->order_type;
        if (isset($data['order_type'])) {
            $updateData['order_type'] = $data['order_type'];
        }

        if ($latToCheck && $lngToCheck) {
            $this->checkDeliveryRadius($cart->restaurant_id, $latToCheck, $lngToCheck, $currentOrderType);
        }

        if (isset($data['order_instructions']))
            $updateData['order_instructions'] = $data['order_instructions'];
        if (isset($data['tip_amount']))
            $updateData['tip_amount'] = (float) $data['tip_amount'];

        if (isset($data['remove_coupon']) && $data['remove_coupon'] == true) {
            $updateData['coupon_id'] = null;
            $updateData['discount'] = 0;
        }

        if (!empty($updateData)) {
            $cart->update($updateData);
        }

        $this->updateCartTotals($cart);

        return $cart->fresh();
    }

    public function removeItem(int $cartItemId, int $userId)
    {
        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            throw new Exception('Cart item not found.', 404);
        }

        $cart = Cart::where('id', $cartItem->cart_id)
            ->where('user_id', $userId)
            ->first();

        if (!$cart) {
            throw new Exception('Unauthorized access to cart.', 403);
        }

        $cartItem->delete();

        $this->updateCartTotals($cart);

        if ($cart->items()->count() === 0) {
            $cart->delete();
        }

        return true;
    }

    public function clearCart($userId)
    {
        $cart = Cart::where('user_id', $userId)->first();

        if (!$cart) {
            throw new Exception('Cart is already empty', 404);
        }

        CartItem::where('cart_id', $cart->id)->delete();
        $cart->delete();

        return true;
    }

    public function updateQuantity($cartItemId, $quantity)
    {
        $cartItem = CartItem::find($cartItemId);
        if (!$cartItem)
            throw new Exception('Cart item not found');

        $menuItem = MenuItem::find($cartItem->menu_item_id);
        if ($quantity > $menuItem->max_cart_quantity) {
            throw new Exception("Maximum allowed quantity is {$menuItem->max_cart_quantity}");
        }

        // ✅ Step 3: changed to final_price
        $cartItem->update([
            'quantity' => $quantity,
            'total_price' => $cartItem->final_price * $quantity,
        ]);

        $cart = Cart::find($cartItem->cart_id);
        $this->updateCartTotals($cart);

        $cart->refresh();
        return [
            'final_price' => currencyFormat($cartItem->fresh()->total_price), // renamed key 'price' to 'final_price'
            'totalPrice' => currencyFormat($cart->subtotal),
            'gst' => currencyFormat($cart->gst_amount),
            'discount' => currencyFormat($cart->discount),
            'total' => currencyFormat($cart->total),
        ];
    }

    public function applyCoupon(array $data, $userId)
{
    // dd($data);
    $today = now();

    $coupon = Coupon::whereRaw(
        'BINARY slug = ?',
        [$data['coupon']]
    )
        ->where('from_date', '<=', $today)
        ->where('to_date', '>=', $today)
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
            'This Coupon is Invalid or Expired',
            422
        );
    }

    if (
        $coupon->restaurant_id != 0 &&
        (int) $coupon->restaurant_id !== (int) $data['restaurant_id']
    ) {
        throw new Exception(
            'This Coupon is Invalid for this restaurant.',
            422
        );
    }

    $subtotal = 0;

    foreach ($data['items'] as $itemData) {

        $menuItem = MenuItem::find(
            $itemData['menu_item_id']
        );

        if (!$menuItem) {
            throw new Exception(
                'Menu item not found.',
                422
            );
        }

        if ($menuItem->status != MenuItemStatus::ACTIVE) {
            throw new Exception(
                "{$menuItem->name} is currently unavailable.",
                422
            );
        }

        if (
            $coupon->restaurant_id != 0 &&
            (int) $coupon->restaurant_id !==
            (int) $data['restaurant_id']
        ) {
            throw new Exception(
                "{$menuItem->name} does not belong to the selected restaurant.",
                422
            );
        }

        $quantity = (int) $itemData['quantity'];

        if ($quantity > $menuItem->max_cart_quantity) {
            throw new Exception(
                "Maximum allowed quantity for {$menuItem->name} is {$menuItem->max_cart_quantity}.",
                422
            );
        }

        $priceDetails = $this->calculateItemPrice(
            $menuItem,
            $itemData['variation_id'] ?? null,
            $itemData['options'] ?? []
        );

        $subtotal +=
            $priceDetails['final_price'] * $quantity;
    }

    $subtotal = round($subtotal, 2);

    $total = round(
        (float) ($data['total'] ?? 0),
        2
    );

    if (
        $coupon->minimum_order_amount > 0 &&
        $total < $coupon->minimum_order_amount
    ) {
        throw new Exception(
            'This coupon requires a minimum order amount of ₹' .
            $coupon->minimum_order_amount,
            422
        );
    }


    $totalUsed = Discount::where(
        'coupon_id',
        $coupon->id
    )
        ->where(
            'status',
            DiscountStatus::ACTIVE
        )
        ->count();

    if ($totalUsed >= $coupon->limit) {
        throw new Exception(
            'This Coupon is fully redeemed and no longer available.',
            422
        );
    }

    $userUsedCount = Discount::where(
        'coupon_id',
        $coupon->id
    )
        ->where(
            'user_id',
            $userId
        )
        ->where(
            'status',
            DiscountStatus::ACTIVE
        )
        ->count();

    $userLimit = $coupon->user_limit > 0
        ? $coupon->user_limit
        : 1;

   

    if ($coupon->discount_type === 'percent') {

        $discount = (
            $total * (float) $coupon->amount
        ) / 100;

    } else {

        $discount = (float) $coupon->amount;
    }

    $discount = min(
        max(0, $discount),
        $total
    );

    $afterCoupon = round(
        $total - $discount,
        2
    );

    return [
        'coupon' => [
            'id' => $coupon->id,
            'code' => $coupon->slug,
            'discount_type' => $coupon->discount_type,
            'amount' => (float) $coupon->amount,
            'minimum_order_amount' => (float) $coupon->minimum_order_amount,
        ],

        'pricing' => [
            'subtotal' => $subtotal,
            'total' => $total,
            'coupon_discount' => round(
                $discount,
                2
            ),
            'after_coupon' => $afterCoupon,
        ],
    ];
}

    private function getCartOrFail($userId)
    {
        $cart = Cart::where('user_id', $userId)->first();
        if (!$cart)
            throw new Exception('Cart not found', 404);
        return $cart;
    }

    private function firstOrCreateCart($userId, $restaurantId, $data)
    {
        $attributes = [
            'coupon_id' => null,
            'subtotal' => 0,
            'discount' => 0,
            'order_type' => OrderTypeStatus::DELIVERY,
            'gst_amount' => 0,
            'delivery_charge' => 0,
            'total' => 0,
            'restaurant_id' => $restaurantId
        ];

        if (isset($data['latitude']) && isset($data['longitude'])) {
            $attributes['latitude'] = $data['latitude'];
            $attributes['longitude'] = $data['longitude'];
        }

        $cart = Cart::firstOrCreate(['user_id' => $userId], $attributes);

        if (isset($data['latitude']) && isset($data['longitude'])) {
            $cart->update(['restaurant_id' => $restaurantId, 'latitude' => $data['latitude'], 'longitude' => $data['longitude']]);
        }

        return $cart;
    }

    // ✅ Step 1: Updated logic
    private function calculateItemPrice($menuItem, $variationId, $optionIds)
    {
        $unitPrice = (float) $menuItem->unit_price;
        $productDiscount = (float) $menuItem->discount_price;

        $finalPrice = max(0, $unitPrice - $productDiscount);

        $finalVariationId = null;
        $variationName = null;

        if ($variationId) {
            $variation = MenuItemVariation::find($variationId);

            if (!$variation) {
                throw new Exception('Variation not found', 404);
            }

            $finalVariationId = $variation->id;
            $variationName = $variation->name;

            $finalPrice += $variation->price;
        }

        $optionArray = [];

        if (!empty($optionIds)) {
            $options = MenuItemOption::whereIn('id', $optionIds)->get();
            foreach ($options as $option) {
                $optionArray[] = [
                    'id' => $option->id,
                    'name' => $option->name,
                    'price' => $option->price
                ];
                $finalPrice += $option->price;
            }
        }

        return [
            'unit_price' => $unitPrice,
            'discount_price' => $productDiscount,
            'final_price' => $finalPrice,
            'variation_id' => $finalVariationId,
            'variation_name' => $variationName,
            'options' => $optionArray,
        ];
    }

    public function updateCartTotals(Cart $cart)
    {
        $cart->loadMissing('coupon');
        $settings = Setting::pluck('value', 'key');

        $subtotal = CartItem::where('cart_id', $cart->id)
            ->where('is_available', true)
            ->sum('total_price');

        // ✅ Step 5: Product Discount Calculation (Only for available items)
        $productDiscount = CartItem::where('cart_id', $cart->id)
            ->where('is_available', true)
            ->sum(DB::raw('discount_price * quantity'));

        $totalQuantity = CartItem::where('cart_id', $cart->id)
            ->where('is_available', true)
            ->sum('quantity');

        if ($cart->coupon) {
            $minOrderAmount = $cart->coupon->minimum_order_amount ?? 0;

            if ($subtotal < $minOrderAmount || $subtotal == 0) {
                $cart->update(['coupon_id' => null, 'discount' => 0]);
                $cart->load('coupon');
            }
        }

        $discount = $this->calculateDiscount($cart, $subtotal);
        $taxableAmount = max(0, $subtotal - $discount);

        $gstPercentage = 5;
        $gstAmount = ($taxableAmount * $gstPercentage) / 100;

        $perItemPackagingCharge = (float) ($settings['packing_charge'] ?? 0);
        $packagingCharge = $totalQuantity * $perItemPackagingCharge;

        $platformFee = $subtotal > 0 ? (float) ($settings['platform_fee'] ?? 0) : 0;

        $surgeFee = $subtotal > 0 ? (float) ($settings['surge_fee'] ?? 0) : 0;

        $deliveryCharge = $this->calculateDeliveryCharge($cart, $settings, $subtotal);

        $largeOrderFee = 0;
        $highOrderAmountLimit = (float) ($settings['high_order_amount_limit'] ?? 0);

        if ($cart->order_type == OrderTypeStatus::DELIVERY && $highOrderAmountLimit > 0 && $subtotal >= $highOrderAmountLimit) {
            $largeOrderFee = (float) ($settings['large_order_fee'] ?? 0);
        }

        $tipAmount = (float) ($cart->tip_amount ?? 0);

        if ($subtotal == 0) {
            $total = 0;
            $deliveryCharge = 0;
            $packagingCharge = 0;
            $platformFee = 0;
            $surgeFee = 0;
            $gstAmount = 0;
            $largeOrderFee = 0;
            $tipAmount = 0;
        } else {
            $total = max(0, $taxableAmount + $gstAmount + $deliveryCharge + $packagingCharge + $platformFee + $surgeFee + $largeOrderFee + $tipAmount);
        }

        // ✅ Step 5: Update Cart values
        $cart->update([
            'subtotal' => $subtotal,
            'product_discount' => $productDiscount,
            'discount' => $discount,
            'gst_amount' => $gstAmount,
            'delivery_charge' => round($deliveryCharge, 2),
            'packing_charge' => round($packagingCharge, 2),
            'platform_fee' => round($platformFee, 2),
            'surge_fee' => round($surgeFee, 2),
            'large_order_fee' => round($largeOrderFee, 2),
            'tip_amount' => round($tipAmount, 2),
            'total' => round($total, 2),
        ]);
    }

    private function calculateDiscount(Cart $cart, $subtotal)
    {
        if (!$cart->coupon)
            return 0;
        $discount = ($cart->coupon->discount_type == 'percent') ? ($subtotal * $cart->coupon->amount) / 100 : $cart->coupon->amount;
        return min($discount, $subtotal);
    }

    private function calculateDeliveryCharge(Cart $cart, $settings, $subtotal)
    {
        if ($cart->order_type != OrderTypeStatus::DELIVERY) {
            return 0;
        }

        $highOrderAmountLimit = (float) ($settings['high_order_amount_limit'] ?? 0);
        $highOrderDeliveryCharge = (float) ($settings['high_order_delivery_charge'] ?? 0);

        if ($highOrderAmountLimit > 0 && $subtotal >= $highOrderAmountLimit) {
            return $highOrderDeliveryCharge;
        }

        $basicDeliveryCharge = (float) ($settings['basic_delivery_charge'] ?? 0);

        $restaurant = Restaurant::find($cart->restaurant_id);
        $address = Address::find($cart->address_id);

        if ($restaurant && $address && $restaurant->lat !== null && $restaurant->long !== null && $address->latitude !== null && $address->longitude !== null) {
            $chargePerKilo = (float) ($settings['charge_per_kilo'] ?? 0);

            $distance = $this->calculateDistance(
                (float) $address->latitude,
                (float) $address->longitude,
                (float) $restaurant->lat,
                (float) $restaurant->long
            );

            if ($distance > 1000) {
                return $basicDeliveryCharge;
            }

            return round($basicDeliveryCharge + ($distance * $chargePerKilo), 2);
        }

        return $basicDeliveryCharge;
    }

    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    private function syncCartItems(Cart $cart)
    {
        $hasChanges = false;
        $priceChanged = false;
        $syncMessages = [];

        foreach ($cart->items as $cartItem) {
            $menuItem = $cartItem->menuItem;

            if (!$menuItem || $menuItem->status != 5) {
                if ($cartItem->is_available) {
                    $cartItem->update([
                        'is_available' => false,
                        'total_price' => 0
                    ]);
                    $hasChanges = true;
                }

                $syncMessages[] = "{$cartItem->menu_name} is temporarily unavailable.";
                continue;
            }

            $optionIds = [];
            if (!empty($cartItem->options) && is_array($cartItem->options)) {
                $optionIds = array_column($cartItem->options, 'id');
            }

            $livePriceDetails = $this->calculateItemPrice(
                $menuItem,
                $cartItem->variation_id,
                $optionIds
            );

            $livePrice = $livePriceDetails['final_price'];

            $wasUnavailable = !$cartItem->is_available;
            $isPriceChanged = ($cartItem->final_price != $livePrice);

            $expectedTotalPrice = $livePrice * $cartItem->quantity;
            $isTotalWrong = ($cartItem->total_price != $expectedTotalPrice);

            if ($isPriceChanged || $wasUnavailable || $isTotalWrong) {
                $cartItem->update([
                    'is_available' => true,
                    'unit_price' => $livePriceDetails['unit_price'],
                    'discount_price' => $livePriceDetails['discount_price'],
                    'final_price' => $livePrice,
                    'total_price' => $expectedTotalPrice,
                    'options' => $livePriceDetails['options'],
                    'is_price_changed' => $isPriceChanged ? true : $cartItem->is_price_changed
                ]);

                $hasChanges = true;
                if ($isPriceChanged) {
                    $priceChanged = true;
                }
            }

            if ($cartItem->quantity > $menuItem->max_cart_quantity) {
                $cartItem->update([
                    'quantity' => $menuItem->max_cart_quantity,
                    'total_price' => $livePrice * $menuItem->max_cart_quantity,
                ]);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $cart->load('items.menuItem', 'items.variation');
        }

        $cart->setAttribute('sync_messages', $syncMessages);

        return $hasChanges;
    }

    private function checkDeliveryRadius($restaurantId, $latitude, $longitude, $orderType = OrderTypeStatus::DELIVERY)
    {
        if ($orderType == OrderTypeStatus::PICKUP) {
            return true;
        }

        if (!$latitude || !$longitude) {
            return true;
        }

        $restaurant = Restaurant::find($restaurantId);
        if (!$restaurant || !$restaurant->lat || !$restaurant->long) {
            return true;
        }

        $distance = $this->calculateDistance(
            (float) $latitude,
            (float) $longitude,
            (float) $restaurant->lat,
            (float) $restaurant->long
        );

        $settings = Setting::pluck('value', 'key');
        $maxRadius = (float) ($settings['max_delivery_radius'] ?? 10);

        if ($distance > $maxRadius) {
            throw new Exception("Sorry! This restaurant does not deliver to your location. Maximum delivery radius is {$maxRadius} km, but you are {$distance} km away.", 422);
        }

        return true;
    }
}