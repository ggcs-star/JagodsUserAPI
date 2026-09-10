<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;
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
                $this->command->error("Menu item {$menuItemId} not found.");
                return;
            }

            $restaurantId = (int) $menuItem->restaurant_id;

            /*
            |-------------------------------------------------------------------------
            | 1. OPTION GROUP - Choose Your Ghee
            |-------------------------------------------------------------------------
            */
            $gheeGroup = MenuItemOptionGroup::updateOrCreate(
                [
                    'menu_item_id' => $menuItemId,
                    'name' => 'Choose Your Ghee',
                ],
                [
                    'restaurant_id' => $restaurantId,
                    'online_display_name' => 'Choose Your Ghee',
                    'selection_type' => 'single',
                    'is_required' => true,
                    'min_selection' => 1,
                    'max_selection' => 1,
                    'max_selection_per_item' => 1,
                    'show_online' => true,
                    'show_dinein_qr' => true,
                    'allow_open_quantity' => false,
                    'sort_order' => 1,
                    'status' => 1,
                ]
            );

            /*
            |-------------------------------------------------------------------------
            | GHEE OPTIONS
            |-------------------------------------------------------------------------
            */
            $gheeOptions = [
                [
                    'name' => 'A2 Cow Ghee',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Premium A2 Ghee',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 2,
                ],
            ];

            foreach ($gheeOptions as $option) {
                MenuItemOption::updateOrCreate(
                    [
                        'menu_item_id' => $menuItemId,
                        'option_group_id' => $gheeGroup->id,
                        'name' => $option['name'],
                    ],
                    [
                        'restaurant_id' => $restaurantId,
                        'price' => $option['price'],
                        'attribute' => $option['attribute'],
                        'sort_order' => $option['sort_order'],
                        'status' => 1,
                    ]
                );
            }

            /*
            |-------------------------------------------------------------------------
            | 2. OPTION GROUP - Choose Your Sweetner
            |-------------------------------------------------------------------------
            */
            $sweetnerGroup = MenuItemOptionGroup::updateOrCreate(
                [
                    'menu_item_id' => $menuItemId,
                    'name' => 'Choose Your Sweetner',
                ],
                [
                    'restaurant_id' => $restaurantId,
                    'online_display_name' => 'Choose Your Sweetner',
                    'selection_type' => 'single',
                    'is_required' => true,
                    'min_selection' => 1,
                    'max_selection' => 1,
                    'max_selection_per_item' => 1,
                    'show_online' => true,
                    'show_dinein_qr' => true,
                    'allow_open_quantity' => false,
                    'sort_order' => 2,
                    'status' => 1,
                ]
            );

            /*
            |-------------------------------------------------------------------------
            | SWEETNER OPTIONS
            |-------------------------------------------------------------------------
            */
            $sweetnerOptions = [
                [
                    'name' => 'Stevya',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Jaggery',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 2,
                ],
            ];

            foreach ($sweetnerOptions as $option) {
                MenuItemOption::updateOrCreate(
                    [
                        'menu_item_id' => $menuItemId,
                        'option_group_id' => $sweetnerGroup->id,
                        'name' => $option['name'],
                    ],
                    [
                        'restaurant_id' => $restaurantId,
                        'price' => $option['price'],
                        'attribute' => $option['attribute'],
                        'sort_order' => $option['sort_order'],
                        'status' => 1,
                    ]
                );
            }

            /*
            |-------------------------------------------------------------------------
            | 3. OPTION GROUP - Choose Your Work
            |-------------------------------------------------------------------------
            */
            $workGroup = MenuItemOptionGroup::updateOrCreate(
                [
                    'menu_item_id' => $menuItemId,
                    'name' => 'Choose Your Work',
                ],
                [
                    'restaurant_id' => $restaurantId,
                    'online_display_name' => 'Choose Your Work',
                    'selection_type' => 'single',
                    'is_required' => false,
                    'min_selection' => 0,
                    'max_selection' => 1,
                    'max_selection_per_item' => 1,
                    'show_online' => true,
                    'show_dinein_qr' => true,
                    'allow_open_quantity' => false,
                    'sort_order' => 3,
                    'status' => 1,
                ]
            );

            /*
            |-------------------------------------------------------------------------
            | WORK OPTIONS
            |-------------------------------------------------------------------------
            */
            $workOptions = [
                [
                    'name' => 'No Vark',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Silver Vark',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Gold Vark',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 3,
                ],
                [
                    'name' => 'Gold + Silver Vark',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 4,
                ],
            ];

            foreach ($workOptions as $option) {
                MenuItemOption::updateOrCreate(
                    [
                        'menu_item_id' => $menuItemId,
                        'option_group_id' => $workGroup->id,
                        'name' => $option['name'],
                    ],
                    [
                        'restaurant_id' => $restaurantId,
                        'price' => $option['price'],
                        'attribute' => $option['attribute'],
                        'sort_order' => $option['sort_order'],
                        'status' => 1,
                    ]
                );
            }

            /*
            |-------------------------------------------------------------------------
            | 4. OPTION GROUP - Choose Cardomom Level
            |-------------------------------------------------------------------------
            */
            $cardomomGroup = MenuItemOptionGroup::updateOrCreate(
                [
                    'menu_item_id' => $menuItemId,
                    'name' => 'Choose Cardomom Level',
                ],
                [
                    'restaurant_id' => $restaurantId,
                    'online_display_name' => 'Choose Cardomom Level',
                    'selection_type' => 'single',
                    'is_required' => true,
                    'min_selection' => 1,
                    'max_selection' => 1,
                    'max_selection_per_item' => 1,
                    'show_online' => true,
                    'show_dinein_qr' => true,
                    'allow_open_quantity' => false,
                    'sort_order' => 4,
                    'status' => 1,
                ]
            );

            /*
            |-------------------------------------------------------------------------
            | CARDOMOM OPTIONS
            |-------------------------------------------------------------------------
            */
            $cardomomOptions = [
                [
                    'name' => 'No Cardomom',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Mild',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Regular',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 3,
                ],
                [
                    'name' => 'Extra',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 4,
                ],
            ];

            foreach ($cardomomOptions as $option) {
                MenuItemOption::updateOrCreate(
                    [
                        'menu_item_id' => $menuItemId,
                        'option_group_id' => $cardomomGroup->id,
                        'name' => $option['name'],
                    ],
                    [
                        'restaurant_id' => $restaurantId,
                        'price' => $option['price'],
                        'attribute' => $option['attribute'],
                        'sort_order' => $option['sort_order'],
                        'status' => 1,
                    ]
                );
            }

            /*
            |-------------------------------------------------------------------------
            | 5. OPTION GROUP - Add Premium Dryfruits
            |-------------------------------------------------------------------------
            */
            $dryfruitGroup = MenuItemOptionGroup::updateOrCreate(
                [
                    'menu_item_id' => $menuItemId,
                    'name' => 'Add Premium Dryfruits',
                ],
                [
                    'restaurant_id' => $restaurantId,
                    'online_display_name' => 'Add Premium Dryfruits',
                    'selection_type' => 'multiple',
                    'is_required' => false,
                    'min_selection' => 0,
                    'max_selection' => 4,
                    'max_selection_per_item' => 1,
                    'show_online' => true,
                    'show_dinein_qr' => true,
                    'allow_open_quantity' => false,
                    'sort_order' => 5,
                    'status' => 1,
                ]
            );

            /*
            |-------------------------------------------------------------------------
            | PREMIUM DRYFRUIT OPTIONS
            |-------------------------------------------------------------------------
            */
            $dryfruitOptions = [
                [
                    'name' => 'No Dry Fruits',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Almond',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Cashew',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 3,
                ],
                [
                    'name' => 'Mixed Dryfruits',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 4,
                ],
                [
                    'name' => 'Pistachio',
                    'price' => 0,
                    'attribute' => 'veg',
                    'sort_order' => 5,
                ],
            ];

            foreach ($dryfruitOptions as $option) {
                MenuItemOption::updateOrCreate(
                    [
                        'menu_item_id' => $menuItemId,
                        'option_group_id' => $dryfruitGroup->id,
                        'name' => $option['name'],
                    ],
                    [
                        'restaurant_id' => $restaurantId,
                        'price' => $option['price'],
                        'attribute' => $option['attribute'],
                        'sort_order' => $option['sort_order'],
                        'status' => 1,
                    ]
                );
            }

            /*
            |-------------------------------------------------------------------------
            | Success Message
            |-------------------------------------------------------------------------
            */
            $this->command->info(
                "Besan Ladoo customization data seeded successfully for MenuItem ID {$menuItemId}."
            );
        });
    }
}