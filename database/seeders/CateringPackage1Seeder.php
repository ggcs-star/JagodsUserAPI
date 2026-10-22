<?php

namespace Database\Seeders;

use App\Models\CateringPackage;
use App\Models\CateringPackageSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CateringPackage1Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $package = CateringPackage::updateOrCreate(
                [
                    'slug' => 'package-1',
                ],
                [
                    'module_id' => 3,
                    'category_id' => null,
                    'name' => 'Package 1',
                    'description' => 'Balanced entry package with live chaat + one specialty counter.',
                    'price' => 1100,
                    'price_type' => 'per_person',
                    'min_guests' => 20,
                    'max_guests' => 200,
                    'lead_time_hours' => 24,
                    'sort_order' => 1,
                    'status' => 1,
                ]
            );

            // Package Sections
            $sections = [
                ['name' => 'Welcome Drinks', 'quantity' => 2],
                ['name' => 'Soups', 'quantity' => 2],
                ['name' => 'Starters', 'quantity' => 2],
                ['name' => 'Salads', 'quantity' => 2],
                ['name' => 'Live Chaat Counter', 'quantity' => 1],
                ['name' => 'Specialty Live Counter', 'quantity' => 1],
                ['name' => 'Main Courses', 'quantity' => 2],
                ['name' => 'Indian Breads', 'quantity' => 2],
                ['name' => 'Rice', 'quantity' => 1],
                ['name' => 'Dal', 'quantity' => 1],
                ['name' => 'Papad', 'quantity' => 1],
                ['name' => 'Pickle', 'quantity' => 1],
                ['name' => 'Regular Sweet', 'quantity' => 1],
                ['name' => 'Dessert', 'quantity' => 1],
                ['name' => 'Water Bottle (200 ml)', 'quantity' => 1],
                ['name' => 'Mouth Freshener', 'quantity' => 1],
            ];

            foreach ($sections as $index => $section) {
                CateringPackageSection::updateOrCreate(
                    [
                        'catering_package_id' => $package->id,
                        'name' => $section['name'],
                    ],
                    [
                        'description' => 'Quantity: ' . $section['quantity'],
                        'selection_type' => 'fixed',
                        'min_selections' => 0,
                        'max_selections' => 0,
                        'sort_order' => $index + 1,
                        'status' => 1,
                    ]
                );
            }
        });

        $this->command?->info(
            'Package 1 and its 16 sections seeded successfully.'
        );
    }
}
