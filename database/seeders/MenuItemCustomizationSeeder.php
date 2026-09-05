<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
use App\Models\MenuItemVariationGroup;
use App\Models\MenuItemVariation;
use App\Models\MenuItemOptionGroup;
use App\Models\MenuItemOption;
use Illuminate\Support\Facades\DB;

class MenuItemCustomizationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | Existing Menu Item
            |--------------------------------------------------------------------------
            |
            | Change this ID according to your menu item.
            |
            */
            $menuItemId = 724;

            $menuItem = MenuItem::find($menuItemId);

            if (!$menuItem) {
                $this->command->error(
                    "Menu item {$menuItemId} not found."
                );

                return;
            }

            $restaurantId = (int) $menuItem->restaurant_id;

            /*
            |--------------------------------------------------------------------------
            | 1. VARIATION GROUP
            |--------------------------------------------------------------------------
            |
            | Example:
            | Preparation
            | Required - Select any 1 option
            |
            */
            $variationGroup = MenuItemVariationGroup::updateOrCreate(
                [
                    'menu_item_id' => $menuItemId,
                    'name' => 'Preparation',
                ],
                [
                    'restaurant_id' => $restaurantId,

                    'online_display_name' =>
                        'Preparation',

                    'selection_type' =>
                        'single',

                    'is_required' =>
                        true,

                    'min_selection' =>
                        1,

                    'max_selection' =>
                        1,

                    'show_online' =>
                        true,

                    'show_dinein_qr' =>
                        true,

                    'allow_open_quantity' =>
                        false,

                    'max_selection_per_item' =>
                        1,

                    'sort_order' =>
                        1,

                    'status' =>
                        1,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 2. VARIATIONS
            |--------------------------------------------------------------------------
            */
            $variations = [
                [
                    'name' => 'Steamed',
                    'price' => 0,
                    'discount_price' => 0,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Fried',
                    'price' => 10,
                    'discount_price' => 0,
                    'sort_order' => 2,
                ],
            ];

            foreach ($variations as $variation) {

                MenuItemVariation::updateOrCreate(
                    [
                        'menu_item_id' =>
                            $menuItemId,

                        'variation_group_id' =>
                            $variationGroup->id,

                        'name' =>
                            $variation['name'],
                    ],
                    [
                        'restaurant_id' =>
                            $restaurantId,

                        'price' =>
                            $variation['price'],

                        'discount_price' =>
                            $variation['discount_price'],

                        'sort_order' =>
                            $variation['sort_order'],

                        'status' =>
                            1,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. OPTION GROUP - Extra Toppings
            |--------------------------------------------------------------------------
            */
            $toppingGroup = MenuItemOptionGroup::updateOrCreate(
                [
                    'menu_item_id' =>
                        $menuItemId,

                    'name' =>
                        'Extra Toppings',
                ],
                [
                    'restaurant_id' =>
                        $restaurantId,

                    'online_display_name' =>
                        'Extra Toppings',

                    'selection_type' =>
                        'multiple',

                    'is_required' =>
                        false,

                    'min_selection' =>
                        0,

                    'max_selection' =>
                        3,

                    'max_selection_per_item' =>
                        1,

                    'show_online' =>
                        true,

                    'show_dinein_qr' =>
                        true,

                    'allow_open_quantity' =>
                        false,

                    'sort_order' =>
                        1,

                    'status' =>
                        1,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 4. OPTIONS - Extra Toppings
            |--------------------------------------------------------------------------
            */
            $toppings = [
                [
                    'external_option_id' => '1150813',
                    'name' => 'Cheese',
                    'price' => 10,
                    'attribute' => 'veg',
                    'sort_order' => 1,
                ],
                [
                    'external_option_id' => '1150814',
                    'name' => 'Extra Patty',
                    'price' => 70,
                    'attribute' => 'veg',
                    'sort_order' => 2,
                ],
                [
                    'external_option_id' => '1150815',
                    'name' => 'Jalapeno',
                    'price' => 20,
                    'attribute' => 'veg',
                    'sort_order' => 3,
                ],
                [
                    'external_option_id' => '1150816',
                    'name' => 'Mayo',
                    'price' => 15,
                    'attribute' => 'veg',
                    'sort_order' => 4,
                ],
            ];

            foreach ($toppings as $option) {

                MenuItemOption::updateOrCreate(
                    [
                        'menu_item_id' =>
                            $menuItemId,

                        'option_group_id' =>
                            $toppingGroup->id,

                        'name' =>
                            $option['name'],
                    ],
                    [
                        'restaurant_id' =>
                            $restaurantId,

                        'external_option_id' =>
                            $option['external_option_id'],

                        'price' =>
                            $option['price'],

                        'attribute' =>
                            $option['attribute'],

                        'sort_order' =>
                            $option['sort_order'],

                        'status' =>
                            1,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. OPTION GROUP - Add Beverage
            |--------------------------------------------------------------------------
            */
            $beverageGroup = MenuItemOptionGroup::updateOrCreate(
                [
                    'menu_item_id' =>
                        $menuItemId,

                    'name' =>
                        'Add Beverage',
                ],
                [
                    'restaurant_id' =>
                        $restaurantId,

                    'online_display_name' =>
                        'Add Beverage',

                    'selection_type' =>
                        'single',

                    'is_required' =>
                        false,

                    'min_selection' =>
                        0,

                    'max_selection' =>
                        1,

                    'max_selection_per_item' =>
                        1,

                    'show_online' =>
                        true,

                    'show_dinein_qr' =>
                        true,

                    'allow_open_quantity' =>
                        false,

                    'sort_order' =>
                        2,

                    'status' =>
                        1,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 6. OPTIONS - Beverage
            |--------------------------------------------------------------------------
            */
            $beverages = [
                [
                    'external_option_id' =>
                        '1150783',

                    'name' =>
                        'Mojito',

                    'price' =>
                        0,

                    'attribute' =>
                        'veg',

                    'sort_order' =>
                        1,
                ],
                [
                    'external_option_id' =>
                        '1150784',

                    'name' =>
                        'Cold Drink',

                    'price' =>
                        30,

                    'attribute' =>
                        'veg',

                    'sort_order' =>
                        2,
                ],
            ];

            foreach ($beverages as $option) {

                MenuItemOption::updateOrCreate(
                    [
                        'menu_item_id' =>
                            $menuItemId,

                        'option_group_id' =>
                            $beverageGroup->id,

                        'name' =>
                            $option['name'],
                    ],
                    [
                        'restaurant_id' =>
                            $restaurantId,

                        'external_option_id' =>
                            $option['external_option_id'],

                        'price' =>
                            $option['price'],

                        'attribute' =>
                            $option['attribute'],

                        'sort_order' =>
                            $option['sort_order'],

                        'status' =>
                            1,
                    ]
                );
            }

            $this->command->info(
                "Customization data seeded successfully for MenuItem ID {$menuItemId}."
            );
        });
    }
}