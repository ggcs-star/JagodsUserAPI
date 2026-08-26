<?php

namespace App\Http\Services;

use App\Models\Address;
use App\Models\MenuItem;
use App\Models\MenuItemOption;
use App\Models\MenuItemVariation;
use App\Models\Restaurant;
use App\Models\Setting;
use App\Enums\OrderTypeStatus;
use App\Enums\MenuItemStatus;
use App\Enums\Module;
use Exception;

class CheckoutValidationService
{
    public function validate(array $data, int $userId): array
    {
        if (
            (int) $data['module_id'] === Module::ALL_OVER_INDIA &&
            (int) $data['order_type'] === OrderTypeStatus::PICKUP
        ) {
            throw new Exception(
                'Pickup is not available for All Over India orders.',
                422
            );
        }

        $restaurant = $this->validateRestaurant(
            (int) $data['restaurant_id'],
            (int) $data['order_type'],
            (int) $data['module_id']
        );

        $address = $this->validateAddress(
            $data['address_id'] ?? null,
            (int) $data['order_type'],
            $userId,
            $restaurant,
            (int) $data['module_id']
        );

        $this->checkForDuplicateItems(
            $data['items']
        );

        $itemsData = $this->validateItems(
            $data['items'],
            (int) $data['restaurant_id'],
            (int) $data['order_type'],
            (int) $data['module_id']
        );

        return [
            'restaurant' => $restaurant,
            'address' => $address,
            'items' => $itemsData['validated_items'],
            'subtotal' => $itemsData['subtotal'],
            'product_discount' => $itemsData['product_discount'],
        ];
    }

    private function checkForDuplicateItems(array $items): void
    {
        $uniqueKeys = collect($items)->map(function ($item) {
            $options = collect($item['options'] ?? [])->pluck('id')->sort()->values()->all();
            return $item['menu_item_id'] . '|' . ($item['variation_id'] ?? 0) . '|' . json_encode($options);
        })->unique();

        if ($uniqueKeys->count() !== count($items)) {
            throw new Exception("Duplicate items found. Please merge same items in your cart before checkout.", 422);
        }
    }

    private function validateItems(array $items, int $restaurantId, int $orderType, int $moduleId): array
    {
        $validatedItems = [];
        $subtotal = 0;
        $totalProductDiscount = 0;
        
        // NAYA LOGIC: Saare price changes track karne ke liye
        $cartUpdates = [];

        foreach ($items as $index => $item) {
            $menuItem = MenuItem::with('module')->find($item['menu_item_id']);

            if (!$menuItem)
                throw new Exception('Menu item not found.', 422);

            if ($menuItem->status != MenuItemStatus::ACTIVE) {
                throw new Exception("{$menuItem->name} is currently unavailable.", 422);
            }

            if ((int) $menuItem->module_id !== (int) $moduleId) {
                throw new Exception(json_encode([
                    'error_type' => 'module_mismatch',
                    'message' => "{$menuItem->name} does not belong to the selected module.",
                    'item_name' => $menuItem->name,
                    'requested_module_id' => $moduleId,
                    'actual_module_id' => (int) $menuItem->module_id,
                ]), 422);
            }

            $moduleSlug = optional($menuItem->module)->slug;
            if ($orderType == OrderTypeStatus::PICKUP && $moduleSlug == Module::ALL_OVER_INDIA_SLUG) {
                throw new Exception("Pickup is not available for All Over India orders ({$menuItem->name}).", 422);
            }

            $quantity = (int) $item['quantity'];
            if ($quantity > $menuItem->max_cart_quantity) {
                throw new Exception("Maximum allowed quantity for {$menuItem->name} is {$menuItem->max_cart_quantity}.", 422);
            }

            // $cartUpdates array reference bheja ja raha hai taaki data collect ho sake
            $priceData = $this->calculateLivePrice($menuItem, $item['variation_id'] ?? null, $item['options'] ?? [], $cartUpdates, $index);

            // Item ki basic price aur discount compare karne ke liye bhi reference bheja
            $this->comparePrice($menuItem, $item, $priceData, $cartUpdates, $index);

            $itemTotal = round($priceData['final_price'] * $quantity, 2);
            $subtotal += $itemTotal;
            $totalProductDiscount += ($priceData['discount_price'] * $quantity);

            $validatedItems[] = array_merge($item, [
                'menu_name' => $menuItem->name,
                'variation_name' => $priceData['variation_name'],
                'item_total' => $itemTotal,
                'options_total' => $priceData['options_total'],
                'unit_price' => $priceData['unit_price'],
                'discount_price' => $priceData['discount_price'],
                'final_price' => $priceData['final_price'],
                'options' => $priceData['options'],
            ]);
        }

        if (!empty($cartUpdates)) {
            throw new Exception(json_encode([
                'error_type' => 'cart_price_updated',
                'message' => 'Some item prices have changed. Please review your cart.',
                'updated_items' => array_values($cartUpdates) 
            ]), 200);
        }

        return [
            'validated_items' => $validatedItems,
            'subtotal' => round($subtotal, 2),
            'product_discount' => round($totalProductDiscount, 2)
        ];
    }

    private function calculateLivePrice(MenuItem $menuItem, ?int $variationId, array $frontendOptions, array &$cartUpdates, int $index): array
    {
        $unitPrice = (float) $menuItem->unit_price;
        $discountPrice = (float) $menuItem->discount_price;
        $finalPrice = max(0, $unitPrice - $discountPrice);

        $variationName = null;
        $finalVariationId = null;

        if ($variationId) {
            $variation = MenuItemVariation::where('id', $variationId)->where('menu_item_id', $menuItem->id)->first();
            if (!$variation)
                throw new Exception("{$menuItem->name}: selected variation is invalid.", 422);

            $finalVariationId = $variation->id;
            $variationName = $variation->name;
            $finalPrice += (float) $variation->price;
        }

        $options = [];
        $optionsTotal = 0;

        foreach ($frontendOptions as $optionData) {
            $option = MenuItemOption::where('id', $optionData['id'])->where('menu_item_id', $menuItem->id)->first();
            if (!$option)
                throw new Exception("Option selected is not valid for {$menuItem->name}.", 422);

            $optionPrice = (float) $option->price;

            // Agar option price change hui hai to data collect kare
            if (abs((float) $optionData['price'] - $optionPrice) > 0.01) {
                if (!isset($cartUpdates[$index])) {
                    $this->initCartUpdate($cartUpdates, $index, $menuItem);
                }
                $cartUpdates[$index]['option_changes'][] = [
                    'option_id' => $option->id,
                    'name' => $option->name,
                    'old_price' => (float) $optionData['price'],
                    'new_price' => $optionPrice
                ];
            }

            $options[] = ['id' => $option->id, 'name' => $option->name, 'price' => $optionPrice];
            $optionsTotal += $optionPrice;
            $finalPrice += $optionPrice;
        }

        return [
            'unit_price' => $unitPrice,
            'discount_price' => $discountPrice,
            'final_price' => round($finalPrice, 2),
            'variation_id' => $finalVariationId,
            'variation_name' => $variationName,
            'options' => $options,
            'options_total' => round($optionsTotal, 2),
        ];
    }

    private function comparePrice(MenuItem $menuItem, array $frontendItem, array $livePrice, array &$cartUpdates, int $index): void
    {
        $frontendUnitPrice = (float) $frontendItem['unit_price'];
        $frontendDiscount = (float) $frontendItem['discount_price'];
        $frontendFinalPrice = (float) $frontendItem['final_price'];

        $changes = [];

        if (abs($frontendUnitPrice - $livePrice['unit_price']) > 0.01) {
            $changes['unit_price'] = ['old' => $frontendUnitPrice, 'new' => $livePrice['unit_price']];
        }
        if (abs($frontendDiscount - $livePrice['discount_price']) > 0.01) {
            $changes['discount_price'] = ['old' => $frontendDiscount, 'new' => $livePrice['discount_price']];
        }
        if (abs($frontendFinalPrice - $livePrice['final_price']) > 0.01) {
            $changes['final_price'] = ['old' => $frontendFinalPrice, 'new' => $livePrice['final_price']];
        }

        // Agar items me changes mile, to collect kar lo
        if (!empty($changes)) {
            if (!isset($cartUpdates[$index])) {
                $this->initCartUpdate($cartUpdates, $index, $menuItem);
            }
            $cartUpdates[$index]['item_changes'] = $changes;
            $cartUpdates[$index]['current_live_prices'] = [
                'unit_price' => $livePrice['unit_price'],
                'discount_price' => $livePrice['discount_price'],
                'final_price' => $livePrice['final_price']
            ];
        }
    }

    // Helper function taaki array baar baar repeat na likhna pade
    private function initCartUpdate(array &$cartUpdates, int $index, MenuItem $menuItem): void
    {
        $cartUpdates[$index] = [
            'cart_index' => $index, // App ko pata chalega ki cart ka kaunsa index tha
            'menu_item_id' => $menuItem->id,
            'name' => $menuItem->name,
            'error_type' => 'price_changed',
            'message' => "Prices for {$menuItem->name} have changed.",
            'item_changes' => [],
            'option_changes' => [],
            'current_live_prices' => []
        ];
    }

    private function validateRestaurant(int $restaurantId, int $orderType, int $moduleId): ?Restaurant 
    {
        if ($moduleId === Module::ALL_OVER_INDIA) return null;

        $restaurant = Restaurant::find($restaurantId);
        if (!$restaurant) throw new Exception('Restaurant not found.', 404);

        if ($restaurant->status != \App\Enums\Status::ACTIVE) {
            throw new Exception("{$restaurant->name} is currently inactive and cannot accept orders.", 422);
        }

        if ($orderType == OrderTypeStatus::DELIVERY && $restaurant->delivery_status != \App\Enums\DeliveryStatus::ENABLE) {
            throw new Exception("Delivery is currently unavailable for {$restaurant->name}.", 422);
        }

        if ($orderType == OrderTypeStatus::PICKUP && $restaurant->pickup_status != \App\Enums\PickupStatus::ENABLE) {
            throw new Exception("Pickup is currently unavailable for {$restaurant->name}.", 422);
        }

        if ($restaurant->current_status != \App\Enums\CurrentStatus::YES) {
            throw new Exception("{$restaurant->name} is temporarily not accepting orders.", 422);
        }

        if (!$restaurant->is_open) {
            throw new Exception("{$restaurant->name} is currently closed.", 422);
        }

        return $restaurant;
    }

    private function validateAddress(?int $addressId, int $orderType, int $userId, ?Restaurant $restaurant, int $moduleId): ?Address 
    {
        if ($orderType != OrderTypeStatus::DELIVERY) return null;

        if (!$addressId) throw new Exception('Delivery address is required.', 422);

        $address = Address::where('id', $addressId)->where('user_id', $userId)->first();
        if (!$address) throw new Exception('Selected delivery address does not belong to you.', 422);

        if ($moduleId === Module::YOUR_CITY) {
            if (!$restaurant) throw new Exception('Restaurant is required for city delivery.', 422);

            if ($restaurant->lat !== null && $restaurant->long !== null && $address->latitude !== null && $address->longitude !== null) {
                $distance = $this->calculateDistance((float) $address->latitude, (float) $address->longitude, (float) $restaurant->lat, (float) $restaurant->long);
                $settings = Setting::pluck('value', 'key');
                $maxRadius = (float) ($settings['max_delivery_radius'] ?? 20);

                if ($maxRadius > 0 && $distance > $maxRadius) {
                    throw new Exception("Sorry! This restaurant does not deliver to your location. Maximum delivery radius is {$maxRadius} km, but you are {$distance} km away.", 422);
                }
            }
        }

        if ($moduleId === Module::ALL_OVER_INDIA) {
            if (blank($address->pincode)) throw new Exception('Delivery pincode is required.', 422);
        }

        return $address;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return round($earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a))), 2);
    }
}