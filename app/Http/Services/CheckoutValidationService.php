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

        $this->checkForDuplicateItems($data['items']);

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
        $uniqueKeys = collect($items)
            ->map(function ($item) {

                $options = collect($item['options'] ?? [])
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all();

                return
                    $item['menu_item_id']
                    . '|'
                    . ($item['variation_id'] ?? 0)
                    . '|'
                    . json_encode($options);
            })
            ->unique();

        if ($uniqueKeys->count() !== count($items)) {
            throw new Exception(
                'Duplicate items found. Please merge same items in your cart before checkout.',
                422
            );
        }
    }

    private function validateItems(
        array $items,
        int $restaurantId,
        int $orderType,
        int $moduleId
    ): array {

        $validatedItems = [];
        $subtotal = 0;
        $totalProductDiscount = 0;

        $cartUpdates = [];

        foreach ($items as $index => $item) {

            $menuItem = MenuItem::with('module')
                ->find($item['menu_item_id']);

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

            if ((int) $menuItem->module_id !== (int) $moduleId) {
                throw new Exception(
                    json_encode([
                        'error_type' => 'module_mismatch',
                        'message' => "{$menuItem->name} does not belong to the selected module.",
                        'item_name' => $menuItem->name,
                        'requested_module_id' => $moduleId,
                        'actual_module_id' => (int) $menuItem->module_id,
                    ]),
                    422
                );
            }

            $moduleSlug = optional($menuItem->module)->slug;

            if (
                $orderType == OrderTypeStatus::PICKUP &&
                $moduleSlug == Module::ALL_OVER_INDIA_SLUG
            ) {
                throw new Exception(
                    "Pickup is not available for All Over India orders ({$menuItem->name}).",
                    422
                );
            }

            $quantity = (int) $item['quantity'];

            if ($quantity <= 0) {
                throw new Exception(
                    "Invalid quantity for {$menuItem->name}.",
                    422
                );
            }

            if ($quantity > $menuItem->max_cart_quantity) {
                throw new Exception(
                    "Maximum allowed quantity for {$menuItem->name} is {$menuItem->max_cart_quantity}.",
                    422
                );
            }

            $frontendOptions = $item['options'] ?? [];

            $optionIds = collect($frontendOptions)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values();

            if ($optionIds->count() !== $optionIds->unique()->count()) {
                throw new Exception(
                    "Duplicate options selected for {$menuItem->name}.",
                    422
                );
            }

            $priceData = $this->calculateLivePrice(
                $menuItem,
                $item['variation_id'] ?? null,
                isset($item['variation_price']) ? (float) $item['variation_price'] : null,
                $frontendOptions,
                $cartUpdates,
                $index
            );

            $this->comparePrice(
                $menuItem,
                $item,
                $priceData,
                $cartUpdates,
                $index
            );

            $itemTotal = round(
                $priceData['final_price'] * $quantity,
                2
            );

            $subtotal += $itemTotal;

            $totalProductDiscount +=
                $priceData['discount_price'] * $quantity;

            $validatedItems[] = array_merge(
                $item,
                [
                    'menu_name' =>
                    $menuItem->name,

                    'variation_id' =>
                    $priceData['variation_id'],

                    'variation_group_id' =>
                    $priceData['variation_group_id'],

                    'variation_group_name' =>
                    $priceData['variation_group_name'],

                    'variation_name' =>
                    $priceData['variation_name'],

                    'variation_price' =>
                    $priceData['variation_price'],

                    'variation_discount_price' =>
                    $priceData['variation_discount_price'],

                    'item_total' =>
                    $itemTotal,

                    'options_total' =>
                    $priceData['options_total'],

                    'unit_price' =>
                    $priceData['unit_price'],

                    'discount_price' =>
                    $priceData['discount_price'],

                    'final_price' =>
                    $priceData['final_price'],

                    'base_final_price' =>
                    $priceData['base_final_price'],

                    'options' =>
                    $priceData['options'],
                ]
            );
        }

        if (!empty($cartUpdates)) {

            throw new Exception(
                json_encode([
                    'error_type' => 'cart_price_updated',
                    'message' => 'Some item prices have changed. Please review your cart.',
                    'updated_items' => array_values($cartUpdates),
                ]),
                200
            );
        }

        return [
            'validated_items' => $validatedItems,
            'subtotal' => round($subtotal, 2),
            'product_discount' => round($totalProductDiscount, 2),
        ];
    }

    private function calculateLivePrice(
        MenuItem $menuItem,
        ?int $variationId,
        ?float $frontendVariationPrice,
        array $frontendOptions,
        array &$cartUpdates,
        int $index
    ): array {

        $unitPrice = (float) $menuItem->unit_price;

        $discountPrice = (float) $menuItem->discount_price;

        $baseFinalPrice = max(
            0,
            $unitPrice - $discountPrice
        );

        $variationIdFinal = null;
        $variationGroupId = null;
        $variationGroupName = null;
        $variationName = null;
        $variationPrice = 0;
        $variationDiscountPrice = 0;

        $variationGroups = $menuItem->variationGroups()
            ->with([
                'variations' => function ($query) {
                    $query
                        ->where('status', 1)
                        ->orderBy('sort_order');
                }
            ])
            ->where('show_online', 1)
            ->orderBy('sort_order')
            ->get();

        foreach ($variationGroups as $group) {

            $selectedVariation = null;

            if ($variationId !== null) {
                $selectedVariation = $group->variations
                    ->firstWhere('id', $variationId);
            }

            if (
                $group->is_required &&
                !$selectedVariation
            ) {
                throw new Exception(
                    "{$group->online_display_name} is required.",
                    422
                );
            }

            if ($selectedVariation) {

                if ($group->selection_type !== 'single') {
                    throw new Exception(
                        "{$group->online_display_name} has invalid selection configuration.",
                        422
                    );
                }

                $variationIdFinal =
                    (int) $selectedVariation->id;

                $variationGroupId =
                    (int) $group->id;

                $variationGroupName =
                    $group->online_display_name
                    ?: $group->name;

                $variationName =
                    $selectedVariation->name;

                $variationPrice =
                    (float) $selectedVariation->price;

                $variationDiscountPrice =
                    (float) $selectedVariation->discount_price;

                if (
                    $frontendVariationPrice !== null &&
                    abs($frontendVariationPrice - $variationPrice) > 0.01
                ) {

                    if (!isset($cartUpdates[$index])) {
                        $this->initCartUpdate(
                            $cartUpdates,
                            $index,
                            $menuItem
                        );
                    }

                    $cartUpdates[$index]['variation_changes'][] = [
                        'variation_group_id' =>
                        $group->id,

                        'variation_group_name' =>
                        $group->online_display_name ?: $group->name,

                        'variation_id' =>
                        $selectedVariation->id,

                        'variation_name' =>
                        $selectedVariation->name,

                        'old_price' =>
                        $frontendVariationPrice,

                        'new_price' =>
                        $variationPrice,
                    ];
                }
            }
        }

        if (
            $variationId !== null &&
            $variationIdFinal === null
        ) {
            throw new Exception(
                "{$menuItem->name}: selected variation is invalid.",
                422
            );
        }

        $variationFinalPrice = max(
            0,
            $variationPrice - $variationDiscountPrice
        );

        $options = [];

        $optionsTotal = 0;

        $optionGroups = $menuItem->optionGroups()
            ->with([
                'options' => function ($query) {
                    $query
                        ->where('status', 1)
                        ->orderBy('sort_order');
                }
            ])
            ->where('show_online', 1)
            ->orderBy('sort_order')
            ->get();

        $frontendOptions = collect($frontendOptions)
            ->map(function ($option) {

                return [
                    'id' => (int) $option['id'],

                    'price' => isset($option['price'])
                        ? (float) $option['price']
                        : null,

                    'quantity' => isset($option['quantity'])
                        ? (int) $option['quantity']
                        : 1,
                ];
            })
            ->values();

        $optionIds = $frontendOptions->pluck('id');

        if (
            $optionIds->count() !==
            $optionIds->unique()->count()
        ) {
            throw new Exception(
                "Duplicate options selected for {$menuItem->name}.",
                422
            );
        }

        foreach ($optionGroups as $group) {

            $groupOptionIds = $group->options
                ->pluck('id')
                ->map(fn($id) => (int) $id);

            $selectedOptions = $frontendOptions
                ->whereIn('id', $groupOptionIds)
                ->values();

            $selectedCount = $selectedOptions->count();

            if (
                $group->is_required &&
                $selectedCount < $group->min_selection
            ) {
                throw new Exception(
                    "{$group->online_display_name} requires at least {$group->min_selection} selection(s).",
                    422
                );
            }

            if (
                $group->max_selection > 0 &&
                $selectedCount > $group->max_selection
            ) {
                throw new Exception(
                    "{$group->online_display_name} allows maximum {$group->max_selection} selection(s).",
                    422
                );
            }

            if (
                $group->selection_type === 'single' &&
                $selectedCount > 1
            ) {
                throw new Exception(
                    "{$group->online_display_name} allows only one selection.",
                    422
                );
            }

            foreach ($selectedOptions as $frontendOption) {

                $option = $group->options->firstWhere(
                    'id',
                    $frontendOption['id']
                );

                if (!$option) {
                    throw new Exception(
                        "Selected option is invalid for {$menuItem->name}.",
                        422
                    );
                }

                $optionPrice = (float) $option->price;

                $optionQuantity =
                    (int) ($frontendOption['quantity'] ?? 1);

                if ($optionQuantity < 1) {
                    throw new Exception(
                        "Invalid quantity for {$option->name}.",
                        422
                    );
                }

                /*
            | Open quantity disabled
            */
                if (
                    !$group->allow_open_quantity &&
                    $optionQuantity !== 1
                ) {
                    throw new Exception(
                        "{$option->name} allows only one quantity.",
                        422
                    );
                }

                /*
            | Maximum quantity per selected option
            */
                if (
                    $group->max_selection_per_item > 0 &&
                    $optionQuantity >
                    $group->max_selection_per_item
                ) {
                    throw new Exception(
                        "Maximum quantity for {$option->name} is {$group->max_selection_per_item}.",
                        422
                    );
                }

                /*
            |--------------------------------------------------------------------------
            | Frontend option price comparison
            |--------------------------------------------------------------------------
            */
                $frontendOptionPrice =
                    $frontendOption['price'];

                if (
                    $frontendOptionPrice !== null &&
                    abs(
                        $frontendOptionPrice -
                            $optionPrice
                    ) > 0.01
                ) {

                    if (!isset($cartUpdates[$index])) {

                        $this->initCartUpdate(
                            $cartUpdates,
                            $index,
                            $menuItem
                        );
                    }

                    $cartUpdates[$index]['option_changes'][] = [
                        'option_group_id' =>
                        $group->id,

                        'option_group_name' =>
                        $group->online_display_name ?: $group->name,

                        'option_id' =>
                        $option->id,

                        'name' =>
                        $option->name,

                        'old_price' =>
                        $frontendOptionPrice,

                        'new_price' =>
                        $optionPrice,
                    ];

                    /*
    |--------------------------------------------------------------------------
    | Current live prices
    |--------------------------------------------------------------------------
    */
                    $cartUpdates[$index]['current_live_prices'] = [
                        'unit_price' =>
                        $unitPrice,

                        'discount_price' =>
                        $discountPrice,

                        'base_final_price' =>
                        $baseFinalPrice,

                        'variation_price' =>
                        $variationFinalPrice,

                        'options_total' =>
                        round(
                            $optionsTotal,
                            2
                        ),

                        'final_price' =>
                        round(
                            $baseFinalPrice
                                + $variationFinalPrice
                                + $optionsTotal,
                            2
                        ),
                    ];
                }

                /*
            |--------------------------------------------------------------------------
            | Snapshot
            |--------------------------------------------------------------------------
            */
                $options[] = [
                    'id' =>
                    $option->id,

                    'option_group_id' =>
                    $group->id,

                    'option_group_name' =>
                    $group->online_display_name
                        ?: $group->name,

                    'external_option_id' =>
                    $option->external_option_id,

                    'name' =>
                    $option->name,

                    'price' =>
                    $optionPrice,

                    'quantity' =>
                    $optionQuantity,

                    'attribute' =>
                    $option->attribute,
                ];

                $optionsTotal +=
                    $optionPrice * $optionQuantity;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Ensure requested options belong to item
    |--------------------------------------------------------------------------
    */
        $validOptionIds = $optionGroups
            ->flatMap(fn($group) => $group->options)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        foreach ($optionIds as $optionId) {

            if (
                !in_array(
                    (int) $optionId,
                    $validOptionIds,
                    true
                )
            ) {
                throw new Exception(
                    "Selected option is invalid for {$menuItem->name}.",
                    422
                );
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Final actual backend price
    |--------------------------------------------------------------------------
    */
        $finalPrice = round(
            $baseFinalPrice
                + $variationFinalPrice
                + $optionsTotal,
            2
        );

        if (!empty($cartUpdates[$index])) {

            $cartUpdates[$index]['current_live_prices'] = [
                'unit_price' =>
                $unitPrice,

                'discount_price' =>
                $discountPrice,

                'base_final_price' =>
                round(
                    $baseFinalPrice,
                    2
                ),

                'variation_price' =>
                round(
                    $variationFinalPrice,
                    2
                ),

                'options_total' =>
                round(
                    $optionsTotal,
                    2
                ),

                'final_price' =>
                $finalPrice,
            ];
        }

        return [
            'unit_price' =>
            $unitPrice,

            'discount_price' =>
            $discountPrice,

            'base_final_price' =>
            round(
                $baseFinalPrice,
                2
            ),

            'variation_id' =>
            $variationIdFinal,

            'variation_group_id' =>
            $variationGroupId,

            'variation_group_name' =>
            $variationGroupName,

            'variation_name' =>
            $variationName,

            'variation_price' =>
            round(
                $variationPrice,
                2
            ),

            'variation_discount_price' =>
            round(
                $variationDiscountPrice,
                2
            ),

            'options' =>
            $options,

            'options_total' =>
            round(
                $optionsTotal,
                2
            ),

            'final_price' =>
            $finalPrice,
        ];
    }

    private function comparePrice(
        MenuItem $menuItem,
        array $frontendItem,
        array $livePrice,
        array &$cartUpdates,
        int $index
    ): void {

        $frontendUnitPrice =
            (float) $frontendItem['unit_price'];

        $frontendDiscount =
            (float) $frontendItem['discount_price'];

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        | frontend final_price = base item final price
        |
        | Example:
        | unit_price      = 190
        | discount_price  = 10
        | final_price     = 180
        |
        | Options:
        | 30 + 70
        |
        | Backend:
        | actual final = 180 + 30 + 70 = 280
        |--------------------------------------------------------------------------
        */
        $frontendBaseFinalPrice =
            (float) $frontendItem['final_price'];

        $changes = [];

        if (
            abs(
                $frontendUnitPrice -
                    $livePrice['unit_price']
            ) > 0.01
        ) {
            $changes['unit_price'] = [
                'old' =>
                $frontendUnitPrice,

                'new' =>
                $livePrice['unit_price'],
            ];
        }

        if (
            abs(
                $frontendDiscount -
                    $livePrice['discount_price']
            ) > 0.01
        ) {
            $changes['discount_price'] = [
                'old' =>
                $frontendDiscount,

                'new' =>
                $livePrice['discount_price'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Compare BASE final price only
        |--------------------------------------------------------------------------
        */
        if (
            abs(
                $frontendBaseFinalPrice -
                    $livePrice['base_final_price']
            ) > 0.01
        ) {
            $changes['final_price'] = [
                'old' =>
                $frontendBaseFinalPrice,

                'new' =>
                $livePrice['base_final_price'],
            ];
        }

        if (!empty($changes)) {

            if (!isset($cartUpdates[$index])) {
                $this->initCartUpdate(
                    $cartUpdates,
                    $index,
                    $menuItem
                );
            }

            $cartUpdates[$index]['item_changes'] = $changes;

            $cartUpdates[$index]['current_live_prices'] = [
                'unit_price' =>
                $livePrice['unit_price'],

                'discount_price' =>
                $livePrice['discount_price'],

                'base_final_price' =>
                $livePrice['base_final_price'],

                'variation_price' =>
                $livePrice['variation_price'],

                'options_total' =>
                $livePrice['options_total'],

                'final_price' =>
                $livePrice['final_price'],
            ];
        }
    }

    private function initCartUpdate(
        array &$cartUpdates,
        int $index,
        MenuItem $menuItem
    ): void {

        $cartUpdates[$index] = [
            'cart_index' =>
            $index,

            'menu_item_id' =>
            $menuItem->id,

            'name' =>
            $menuItem->name,

            'error_type' =>
            'price_changed',

            'message' =>
            "Prices for {$menuItem->name} have changed.",

            'item_changes' =>
            [],

            'variation_changes' =>
            [],

            'option_changes' =>
            [],

            'current_live_prices' =>
            [],
        ];
    }

    private function validateRestaurant(
        int $restaurantId,
        int $orderType,
        int $moduleId
    ): ?Restaurant {

        if (
            $moduleId === Module::ALL_OVER_INDIA
        ) {
            return null;
        }

        $restaurant = Restaurant::find(
            $restaurantId
        );

        if (!$restaurant) {
            throw new Exception(
                'Restaurant not found.',
                404
            );
        }

        if (
            $restaurant->status != \App\Enums\Status::ACTIVE
        ) {
            throw new Exception(
                "{$restaurant->name} is currently inactive and cannot accept orders.",
                422
            );
        }

        if (
            $orderType == OrderTypeStatus::DELIVERY &&
            $restaurant->delivery_status !=
            \App\Enums\DeliveryStatus::ENABLE
        ) {
            throw new Exception(
                "Delivery is currently unavailable for {$restaurant->name}.",
                422
            );
        }

        if (
            $orderType == OrderTypeStatus::PICKUP &&
            $restaurant->pickup_status !=
            \App\Enums\PickupStatus::ENABLE
        ) {
            throw new Exception(
                "Pickup is currently unavailable for {$restaurant->name}.",
                422
            );
        }

        if (
            $restaurant->current_status !=
            \App\Enums\CurrentStatus::YES
        ) {
            throw new Exception(
                "{$restaurant->name} is temporarily not accepting orders.",
                422
            );
        }

        if (!$restaurant->is_open) {
            throw new Exception(
                "{$restaurant->name} is currently closed.",
                422
            );
        }

        return $restaurant;
    }

    private function validateAddress(
        ?int $addressId,
        int $orderType,
        int $userId,
        ?Restaurant $restaurant,
        int $moduleId
    ): ?Address {

        if (
            $orderType != OrderTypeStatus::DELIVERY
        ) {
            return null;
        }

        if (!$addressId) {
            throw new Exception(
                'Delivery address is required.',
                422
            );
        }

        $address = Address::where(
            'id',
            $addressId
        )
            ->where(
                'user_id',
                $userId
            )
            ->first();

        if (!$address) {
            throw new Exception(
                'Selected delivery address does not belong to you.',
                422
            );
        }

        if (
            $moduleId === Module::YOUR_CITY
        ) {

            if (!$restaurant) {
                throw new Exception(
                    'Restaurant is required for city delivery.',
                    422
                );
            }

            if (
                $restaurant->lat !== null &&
                $restaurant->long !== null &&
                $address->latitude !== null &&
                $address->longitude !== null
            ) {

                $distance =
                    $this->calculateDistance(
                        (float) $address->latitude,
                        (float) $address->longitude,
                        (float) $restaurant->lat,
                        (float) $restaurant->long
                    );

                $settings =
                    Setting::pluck(
                        'value',
                        'key'
                    );

                $maxRadius =
                    (float) (
                        $settings['max_delivery_radius']
                        ?? 20
                    );

                if (
                    $maxRadius > 0 &&
                    $distance > $maxRadius
                ) {
                    throw new Exception(
                        "Sorry! This restaurant does not deliver to your location. Maximum delivery radius is {$maxRadius} km, but you are {$distance} km away.",
                        422
                    );
                }
            }
        }

        if (
            $moduleId === Module::ALL_OVER_INDIA
        ) {

            if (blank($address->pincode)) {
                throw new Exception(
                    'Delivery pincode is required.',
                    422
                );
            }
        }

        return $address;
    }

    private function calculateDistance(
        $lat1,
        $lon1,
        $lat2,
        $lon2
    ): float {

        $earthRadius = 6371;

        $dLat = deg2rad(
            $lat2 - $lat1
        );

        $dLon = deg2rad(
            $lon2 - $lon1
        );

        $a =
            sin($dLat / 2) *
            sin($dLat / 2)
            +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) *
            sin($dLon / 2);

        return round(
            $earthRadius *
                (
                    2 *
                    atan2(
                        sqrt($a),
                        sqrt(1 - $a)
                    )
                ),
            2
        );
    }
}
