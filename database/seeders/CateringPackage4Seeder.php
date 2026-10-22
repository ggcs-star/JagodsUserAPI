<?php

namespace Database\Seeders;

use App\Models\CateringPackage;
use App\Models\CateringPackageSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CateringPackage4Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $package = CateringPackage::updateOrCreate(
                [
                    'slug' => 'package-4',
                ],
                [
                    'module_id' => 3,
                    'category_id' => null,
                    'name' => 'Package 4',
                    'description' => 'Premium multi-counter menu with high variety across starters, chaat and sweets.',
                    'price' => 1799,
                    'price_type' => 'per_person',
                    'min_guests' => 20,
                    'max_guests' => 200,
                    'lead_time_hours' => 24,
                    'sort_order' => 4,
                    'status' => 1,
                ]
            );

            // Package 4 Sections
            $sections = [
                ['name' => 'Welcome Drinks', 'quantity' => 3],
                ['name' => 'Soups', 'quantity' => 2],
                ['name' => 'Starters', 'quantity' => 5],
                ['name' => 'Salads', 'quantity' => 3],
                ['name' => 'Bread Accompaniments', 'quantity' => 3],
                ['name' => 'Chaat Counters', 'quantity' => 4],
                ['name' => 'Specialty Live Counters', 'quantity' => 3],
                ['name' => 'Farsan / Baked Dish / Curd Preparations', 'quantity' => 2],
                ['name' => 'Main Courses', 'quantity' => 4],
                ['name' => 'Indian Breads', 'quantity' => 4],
                ['name' => 'Rice', 'quantity' => 1],
                ['name' => 'Dal', 'quantity' => 1],
                ['name' => 'Papad', 'quantity' => 1],
                ['name' => 'Pickle', 'quantity' => 1],
                ['name' => 'Regular Sweets', 'quantity' => 3],
                ['name' => 'Premium Sweets', 'quantity' => 2],
                ['name' => 'Desserts', 'quantity' => 2],
                ['name' => 'Water Bottle (200 ml)', 'quantity' => 1],
                ['name' => 'Mouth Fresheners', 'quantity' => 2],
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
            'Package 4 and its 19 sections seeded successfully.'
        );
    }
}
