<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        Module::truncate();

        Module::insert([
            [
                'id' => 1,
                'name' => 'Your City',
                'slug' => 'your_city',
                'status' => 1,
            ],
            [
                'id' => 2,
                'name' => 'All Over India',
                'slug' => 'all_over_india',
                'status' => 1,
            ]
        ]);
    }
}