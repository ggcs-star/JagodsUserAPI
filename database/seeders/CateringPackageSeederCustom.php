<?php

namespace Database\Seeders;

use App\Models\CateringPackage;
use App\Models\CateringPackageItem;
use App\Models\CateringPackageSection;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CateringPackageSeederCustom extends Seeder
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

            if ($menuItems->count() < 25) {
                $this->command?->warn(
                    'At least 25 active menu items are required.'
                );

                $this->command?->warn(
                    'Currently available: ' . $menuItems->count()
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Convert Menu Items To Array
            |--------------------------------------------------------------------------
            */

            $items = $menuItems->values();

            /*
            |--------------------------------------------------------------------------
            | Fully Customizable Package
            |--------------------------------------------------------------------------
            */

            $package = CateringPackage::updateOrCreate(
                [
                    'slug' => 'premium-fully-custom-catering-package',
                ],
                [
                    'module_id' => 3,
                    'name' => 'Premium Fully Custom Catering Package',
                    'description' =>
                        'Fully customizable catering package. Customers can select items from every section according to their requirements.',
                    'price' => 799,
                    'price_type' => 'per_person',
                    'min_guests' => 50,
                    'max_guests' => 1000,
                    'lead_time_hours' => 48,
                    'sort_order' => 1,
                    'status' => 1,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Sections
            |--------------------------------------------------------------------------
            */

            $sections = [
                [
                    'name' => 'Welcome Drinks',
                    'description' =>
                        'Choose your preferred welcome drinks.',
                    'min' => 1,
                    'max' => 3,
                    'items' => 4,
                ],

                [
                    'name' => 'Soups',
                    'description' =>
                        'Select soups according to your event requirement.',
                    'min' => 1,
                    'max' => 2,
                    'items' => 3,
                ],

                [
                    'name' => 'Starters',
                    'description' =>
                        'Choose your preferred starters.',
                    'min' => 3,
                    'max' => 5,
                    'items' => 5,
                ],

                [
                    'name' => 'Main Course',
                    'description' =>
                        'Customize your main course selection.',
                    'min' => 4,
                    'max' => 8,
                    'items' => 8,
                ],

                [
                    'name' => 'Breads',
                    'description' =>
                        'Choose breads for your catering package.',
                    'min' => 2,
                    'max' => 4,
                    'items' => 5,
                ],

                [
                    'name' => 'Rice',
                    'description' =>
                        'Select rice or pulao options.',
                    'min' => 1,
                    'max' => 2,
                    'items' => 4,
                ],

                [
                    'name' => 'Dal',
                    'description' =>
                        'Select your preferred dal.',
                    'min' => 1,
                    'max' => 2,
                    'items' => 3,
                ],

                [
                    'name' => 'Salads',
                    'description' =>
                        'Customize your salad selection.',
                    'min' => 2,
                    'max' => 4,
                    'items' => 5,
                ],

                [
                    'name' => 'Accompaniments',
                    'description' =>
                        'Choose accompaniments and sides.',
                    'min' => 1,
                    'max' => 3,
                    'items' => 4,
                ],

                [
                    'name' => 'Desserts',
                    'description' =>
                        'Select desserts for your event.',
                    'min' => 2,
                    'max' => 4,
                    'items' => 5,
                ],
            ];

            /*
            |--------------------------------------------------------------------------
            | Create Sections
            |--------------------------------------------------------------------------
            */

            foreach ($sections as $sectionIndex => $sectionData) {

                $section = CateringPackageSection::updateOrCreate(
                    [
                        'catering_package_id' => $package->id,
                        'name' => $sectionData['name'],
                    ],
                    [
                        'description' => $sectionData['description'],

                        // Fully customizable
                        'selection_type' => 'custom',

                        'min_selections' => $sectionData['min'],
                        'max_selections' => $sectionData['max'],

                        'sort_order' => $sectionIndex + 1,
                        'status' => 1,
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Remove Existing Items
                |--------------------------------------------------------------------------
                */

                CateringPackageItem::where(
                    'catering_package_section_id',
                    $section->id
                )->delete();

                /*
                |--------------------------------------------------------------------------
                | Select Menu Items For This Section
                |--------------------------------------------------------------------------
                */

                $sectionItems = $items
                    ->slice(
                        $this->getStartIndex(
                            $sectionIndex,
                            $items->count()
                        ),
                        $sectionData['items']
                    )
                    ->values();

                /*
                |--------------------------------------------------------------------------
                | Create Package Items
                |--------------------------------------------------------------------------
                */

                foreach ($sectionItems as $index => $menuItem) {

                    /*
                    |--------------------------------------------------------------------------
                    | Default Items
                    |
                    | First 1-3 items will be default depending on section.
                    |--------------------------------------------------------------------------
                    */

                    $defaultCount = min(
                        max(1, $sectionData['min'] - 1),
                        $sectionData['items']
                    );

                    $isDefault = $index < $defaultCount;

                    /*
                    |--------------------------------------------------------------------------
                    | Extra Price
                    |
                    | First few default items = no extra price
                    | Optional premium items = extra price
                    |--------------------------------------------------------------------------
                    */

                    $extraPrice = 0;

                    if (!$isDefault) {
                        $extraPrice = match (true) {
                            $index === $defaultCount => 50,
                            $index === $defaultCount + 1 => 75,
                            default => 100,
                        };
                    }

                    CateringPackageItem::create([
                        'catering_package_section_id' =>
                            $section->id,

                        'menu_item_id' =>
                            $menuItem->id,

                        'extra_price' =>
                            $extraPrice,

                        'is_default' =>
                            $isDefault,

                        'sort_order' =>
                            $index + 1,

                        'status' => 1,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Output
            |--------------------------------------------------------------------------
            */

            $this->command?->info(
                "Fully customizable catering package created successfully."
            );

            $this->command?->info(
                "Package ID: {$package->id}"
            );
        });
    }

    /**
     * Generate different starting points so
     * sections don't always contain exactly
     * the same menu items.
     */
    private function getStartIndex(
        int $sectionIndex,
        int $totalItems
    ): int {
        $start = ($sectionIndex * 3) % $totalItems;

        return $start;
    }
}