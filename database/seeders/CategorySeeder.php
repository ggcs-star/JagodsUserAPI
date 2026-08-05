<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\CategoryGroup;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Groups
        |--------------------------------------------------------------------------
        */

        $groceryGroup = CategoryGroup::where('slug', 'grocery-kitchen')->first();

        $snacksGroup = CategoryGroup::where('slug', 'snacks-drinks')->first();

        $householdGroup = CategoryGroup::where('slug', 'household-essentials')->first();

        $personalGroup = CategoryGroup::where('slug', 'personal-care')->first();



        /*
        |--------------------------------------------------------------------------
        | MAIN CATEGORIES
        |--------------------------------------------------------------------------
        */

        $vegetables = Category::create([
            'name' => 'Vegetables & Fruits',
            'slug' => Str::slug('Vegetables & Fruits'),
            'status' => 5,
            'requested' => 10,
            'parent_id' => null,
            'module_id' => 2,
            'category_group_id' => $groceryGroup->id,
        ]);

        $atta = Category::create([
            'name' => 'Atta, Rice & Dal',
            'slug' => Str::slug('Atta, Rice & Dal'),
            'status' => 5,
            'requested' => 10,
            'parent_id' => null,
            'module_id' => 2,
            'category_group_id' => $groceryGroup->id,
        ]);

        $dairy = Category::create([
            'name' => 'Dairy, Bread & Eggs',
            'slug' => Str::slug('Dairy, Bread & Eggs'),
            'status' => 5,
            'requested' => 10,
            'parent_id' => null,
            'module_id' => 2,
            'category_group_id' => $groceryGroup->id,
        ]);

        $snacks = Category::create([
            'name' => 'Snacks',
            'slug' => Str::slug('Snacks'),
            'status' => 5,
            'requested' => 10,
            'parent_id' => null,
            'module_id' => 2,
            'category_group_id' => $snacksGroup->id,
        ]);

        $household = Category::create([
            'name' => 'Cleaning',
            'slug' => Str::slug('Cleaning'),
            'status' => 5,
            'requested' => 10,
            'parent_id' => null,
            'module_id' => 2,
            'category_group_id' => $householdGroup->id,
        ]);

        $personal = Category::create([
            'name' => 'Hair Care',
            'slug' => Str::slug('Hair Care'),
            'status' => 5,
            'requested' => 10,
            'parent_id' => null,
            'module_id' => 2,
            'category_group_id' => $personalGroup->id,
        ]);



        /*
        |--------------------------------------------------------------------------
        | SUB CATEGORIES
        |--------------------------------------------------------------------------
        */

        Category::insert([

            // Vegetables

            [
                'name'=>'Fresh Vegetables',
                'slug'=>'fresh-vegetables',
                'parent_id'=>$vegetables->id,
                'module_id'=>2,
                'category_group_id'=>$groceryGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            [
                'name'=>'Fresh Fruits',
                'slug'=>'fresh-fruits',
                'parent_id'=>$vegetables->id,
                'module_id'=>2,
                'category_group_id'=>$groceryGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            [
                'name'=>'Exotic Fruits',
                'slug'=>'exotic-fruits',
                'parent_id'=>$vegetables->id,
                'module_id'=>2,
                'category_group_id'=>$groceryGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            // Atta

            [
                'name'=>'Rice',
                'slug'=>'rice',
                'parent_id'=>$atta->id,
                'module_id'=>2,
                'category_group_id'=>$groceryGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            [
                'name'=>'Dal',
                'slug'=>'dal',
                'parent_id'=>$atta->id,
                'module_id'=>2,
                'category_group_id'=>$groceryGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            // Dairy

            [
                'name'=>'Milk',
                'slug'=>'milk',
                'parent_id'=>$dairy->id,
                'module_id'=>2,
                'category_group_id'=>$groceryGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            [
                'name'=>'Bread',
                'slug'=>'bread',
                'parent_id'=>$dairy->id,
                'module_id'=>2,
                'category_group_id'=>$groceryGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            // Snacks

            [
                'name'=>'Chips',
                'slug'=>'chips',
                'parent_id'=>$snacks->id,
                'module_id'=>2,
                'category_group_id'=>$snacksGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            [
                'name'=>'Biscuits',
                'slug'=>'biscuits',
                'parent_id'=>$snacks->id,
                'module_id'=>2,
                'category_group_id'=>$snacksGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            // Household

            [
                'name'=>'Floor Cleaner',
                'slug'=>'floor-cleaner',
                'parent_id'=>$household->id,
                'module_id'=>2,
                'category_group_id'=>$householdGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

            // Personal Care

            [
                'name'=>'Shampoo',
                'slug'=>'shampoo',
                'parent_id'=>$personal->id,
                'module_id'=>2,
                'category_group_id'=>$personalGroup->id,
                'status'=>5,
                'requested'=>10,
            ],

        ]);
    }
}