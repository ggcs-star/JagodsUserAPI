<?php

namespace Database\Seeders;

use App\Models\CateringPackage;
use App\Models\CateringPackageSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CateringPackage5Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $package = CateringPackage::updateOrCreate(
                [
                    'slug' => 'package-5',
                ],
                [
                    'module_id' => 3,
                    'category_id' => null,
                    'name' => 'Package 5',
                    'description' => 'Top-tier package with the widest beverage, chaat, accompaniment, sweet and dessert selection.',
                    'price' => 2200,
                    'price_type' => 'per_person',
                    'min_guests' => 20,
                    'max_guests' => 200,
                    'lead_time_hours' => 24,
                    'sort_order' => 5,
                    'status' => 1,
                ]
            );

            // Package 5 Sections
            $sections = [
                ['name' => 'Welcome Drinks', 'quantity' => 5],
                ['name' => 'Soups', 'quantity' => 3],
                ['name' => 'Starters', 'quantity' => 5],
                ['name' => 'Salads', 'quantity' => 5],
                ['name' => 'Bread Accompaniments', 'quantity' => 5],
                ['name' => 'Chaat Counters', 'quantity' => 5],
                ['name' => 'Specialty Live Counters', 'quantity' => 4],
                ['name' => 'Farsan / Baked Dish / Curd Preparations', 'quantity' => 2],
                ['name' => 'Main Courses', 'quantity' => 4],
                ['name' => 'Indian Breads', 'quantity' => 4],
                ['name' => 'Rice', 'quantity' => 2],
                ['name' => 'Dal', 'quantity' => 2],
                ['name' => 'Papad', 'quantity' => 1],
                ['name' => 'Pickle', 'quantity' => 1],
                ['name' => 'Regular Sweets', 'quantity' => 3],
                ['name' => 'Premium Sweets', 'quantity' => 3],
                ['name' => 'Desserts', 'quantity' => 3],
                ['name' => 'Water Bottle (200 ml)', 'quantity' => 1],
                ['name' => 'Mouth Fresheners', 'quantity' => 3],
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
            'Package 5 and its 19 sections seeded successfully.'
        );
    }
}
