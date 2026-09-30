<?php

namespace Database\Seeders;

use App\Models\CateringPackage;
use App\Models\CateringPackageItem;
use App\Models\CateringPackageSection;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CateringPackageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | Get Existing Menu Items
            |--------------------------------------------------------------------------
            */

            $menuItems = MenuItem::query()
                ->where('status', 5)
                ->orderBy('id')
                ->get();

            if ($menuItems->count() < 8) {
                $this->command?->warn(
                    'At least 8 active menu items are required for CateringPackageSeeder.'
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Package 1 - Fixed Package
            |--------------------------------------------------------------------------
            */

            $fixedPackage = CateringPackage::updateOrCreate(
                [
                    'slug' => 'silver-fixed-catering-package',
                ],
                [
                    'module_id' => 3,

                    'name' => 'Silver Fixed Catering Package',

                    'description' =>
                        'A fixed catering package with pre-selected items.',

                    'price' => 499,

                    'price_type' => 'per_person',

                    'min_guests' => 20,

                    'max_guests' => 200,

                    'lead_time_hours' => 24,

                    'sort_order' => 1,

                    'status' => 1,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Fixed Section
            |--------------------------------------------------------------------------
            */

            $welcomeDrink = CateringPackageSection::updateOrCreate(
                [
                    'catering_package_id' => $fixedPackage->id,
                    'name' => 'Welcome Drinks',
                ],
                [
                    'description' =>
                        'Welcome drinks included in the package.',

                    'selection_type' => 'fixed',

                    'min_selections' => 0,

                    'max_selections' => 0,

                    'sort_order' => 1,

                    'status' => 1,
                ]
            );

            $this->addItems(
                $welcomeDrink,
                [
                    [
                        'menu_item_id' => $menuItems[0]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                    [
                        'menu_item_id' => $menuItems[1]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Fixed Starter Section
            |--------------------------------------------------------------------------
            */

            $fixedStarter = CateringPackageSection::updateOrCreate(
                [
                    'catering_package_id' => $fixedPackage->id,
                    'name' => 'Fixed Starters',
                ],
                [
                    'description' =>
                        'These starters are automatically included.',

                    'selection_type' => 'fixed',

                    'min_selections' => 0,

                    'max_selections' => 0,

                    'sort_order' => 2,

                    'status' => 1,
                ]
            );

            $this->addItems(
                $fixedStarter,
                [
                    [
                        'menu_item_id' => $menuItems[2]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                    [
                        'menu_item_id' => $menuItems[3]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Package 2 - Custom Package
            |--------------------------------------------------------------------------
            */

            $customPackage = CateringPackage::updateOrCreate(
                [
                    'slug' => 'gold-custom-catering-package',
                ],
                [
                    'module_id' => 3,

                    'name' => 'Gold Custom Catering Package',

                    'description' =>
                        'Customizable catering package where customers can select items.',

                    'price' => 699,

                    'price_type' => 'per_person',

                    'min_guests' => 30,

                    'max_guests' => 500,

                    'lead_time_hours' => 48,

                    'sort_order' => 2,

                    'status' => 1,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Custom Starter Section
            |--------------------------------------------------------------------------
            */

            $customStarter = CateringPackageSection::updateOrCreate(
                [
                    'catering_package_id' => $customPackage->id,
                    'name' => 'Starters',
                ],
                [
                    'description' =>
                        'Choose your preferred starters.',

                    'selection_type' => 'custom',

                    'min_selections' => 2,

                    'max_selections' => 3,

                    'sort_order' => 1,

                    'status' => 1,
                ]
            );

            $this->addItems(
                $customStarter,
                [
                    [
                        'menu_item_id' => $menuItems[0]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                    [
                        'menu_item_id' => $menuItems[1]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                    [
                        'menu_item_id' => $menuItems[2]->id,
                        'extra_price' => 30,
                        'is_default' => false,
                    ],
                    [
                        'menu_item_id' => $menuItems[3]->id,
                        'extra_price' => 50,
                        'is_default' => false,
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Custom Main Course Section
            |--------------------------------------------------------------------------
            */

            $mainCourse = CateringPackageSection::updateOrCreate(
                [
                    'catering_package_id' => $customPackage->id,
                    'name' => 'Main Course',
                ],
                [
                    'description' =>
                        'Select your preferred main course items.',

                    'selection_type' => 'custom',

                    'min_selections' => 3,

                    'max_selections' => 5,

                    'sort_order' => 2,

                    'status' => 1,
                ]
            );

            $this->addItems(
                $mainCourse,
                [
                    [
                        'menu_item_id' => $menuItems[2]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                    [
                        'menu_item_id' => $menuItems[3]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                    [
                        'menu_item_id' => $menuItems[4]->id,
                        'extra_price' => 25,
                        'is_default' => true,
                    ],
                    [
                        'menu_item_id' => $menuItems[5]->id,
                        'extra_price' => 50,
                        'is_default' => false,
                    ],
                    [
                        'menu_item_id' => $menuItems[6]->id,
                        'extra_price' => 75,
                        'is_default' => false,
                    ],
                    [
                        'menu_item_id' => $menuItems[7]->id,
                        'extra_price' => 100,
                        'is_default' => false,
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Custom Dessert Section
            |--------------------------------------------------------------------------
            */

            $dessert = CateringPackageSection::updateOrCreate(
                [
                    'catering_package_id' => $customPackage->id,
                    'name' => 'Desserts',
                ],
                [
                    'description' =>
                        'Choose one or two desserts.',

                    'selection_type' => 'custom',

                    'min_selections' => 1,

                    'max_selections' => 2,

                    'sort_order' => 3,

                    'status' => 1,
                ]
            );

            $this->addItems(
                $dessert,
                [
                    [
                        'menu_item_id' => $menuItems[0]->id,
                        'extra_price' => 0,
                        'is_default' => true,
                    ],
                    [
                        'menu_item_id' => $menuItems[4]->id,
                        'extra_price' => 20,
                        'is_default' => false,
                    ],
                    [
                        'menu_item_id' => $menuItems[5]->id,
                        'extra_price' => 40,
                        'is_default' => false,
                    ],
                ]
            );
        });

        $this->command?->info(
            'Catering packages seeded successfully.'
        );
    }

    /**
     * Add items to a catering package section.
     */
    private function addItems(
        CateringPackageSection $section,
        array $items
    ): void {
        foreach ($items as $index => $item) {

            CateringPackageItem::updateOrCreate(
                [
                    'catering_package_section_id' => $section->id,
                    'menu_item_id' => $item['menu_item_id'],
                ],
                [
                    'extra_price' => $item['extra_price'] ?? 0,

                    'is_default' =>
                        $item['is_default'] ?? false,

                    'sort_order' => $index + 1,

                    'status' => 1,
                ]
            );
        }
    }
}