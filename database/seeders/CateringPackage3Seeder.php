<?php

namespace Database\Seeders;

use App\Models\CateringPackage;
use App\Models\CateringPackageSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CateringPackage3Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $package = CateringPackage::updateOrCreate(
                [
                    'slug' => 'package-3',
                ],
                [
                    'module_id' => 3,
                    'category_id' => null,
                    'name' => 'Package 3',
                    'description' => 'Stronger starter, salad and chaat spread; suitable for larger social events.',
                    'price' => 1499,
                    'price_type' => 'per_person',
                    'min_guests' => 20,
                    'max_guests' => 200,
                    'lead_time_hours' => 24,
                    'sort_order' => 3,
                    'status' => 1,
                ]
            );

            // Package 3 Sections
            $sections = [
                ['name' => 'Welcome Drinks', 'quantity' => 2],
                ['name' => 'Soups', 'quantity' => 2],
                ['name' => 'Starters', 'quantity' => 4],
                ['name' => 'Salads', 'quantity' => 3],
                ['name' => 'Bread Accompaniments', 'quantity' => 2],
                ['name' => 'Chaat Counters', 'quantity' => 3],
                ['name' => 'Specialty Live Counters', 'quantity' => 2],
                ['name' => 'Farsan / Baked Dish / Curd Preparation', 'quantity' => 1],
                ['name' => 'Main Courses', 'quantity' => 3],
                ['name' => 'Indian Breads', 'quantity' => 3],
                ['name' => 'Rice', 'quantity' => 1],
                ['name' => 'Dal', 'quantity' => 1],
                ['name' => 'Papad', 'quantity' => 1],
                ['name' => 'Pickle', 'quantity' => 1],
                ['name' => 'Regular Sweets', 'quantity' => 2],
                ['name' => 'Premium Sweet', 'quantity' => 1],
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
            'Package 3 and its 19 sections seeded successfully.'
        );
    }
}
