<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\CategoryGroup;

class CategoryGroupSeeder extends Seeder
{
    public function run(): void
    {
        CategoryGroup::insert([

            [
                'module_id'  => 2,
                'name'       => 'Grocery & Kitchen',
                'slug'       => Str::slug('Grocery & Kitchen'),
                'sort_order' => 1,
                'status'     => 1,
            ],

            [
                'module_id'  => 2,
                'name'       => 'Snacks & Drinks',
                'slug'       => Str::slug('Snacks & Drinks'),
                'sort_order' => 2,
                'status'     => 1,
            ],

            [
                'module_id'  => 2,
                'name'       => 'Household Essentials',
                'slug'       => Str::slug('Household Essentials'),
                'sort_order' => 3,
                'status'     => 1,
            ],

            [
                'module_id'  => 2,
                'name'       => 'Personal Care',
                'slug'       => Str::slug('Personal Care'),
                'sort_order' => 4,
                'status'     => 1,
            ],

        ]);
    }
}