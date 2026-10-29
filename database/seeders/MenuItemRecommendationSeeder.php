<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\MenuItemRecommendation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemRecommendationSeeder extends Seeder
{
    public function run(): void
    {
        // Existing menu items fetch karo
        $items = MenuItem::query()
            ->select('id', 'name', 'module_id', 'restaurant_id')
            ->get();

        if ($items->isEmpty()) {
            $this->command->warn('No menu items found. Seeder skipped.');
            return;
        }

        $recommendations = [];

        foreach ($items as $item) {

            // Same module aur restaurant ke items hi recommend honge
            $suggestions = $items
                ->where('module_id', $item->module_id)
                ->filter(function ($suggestion) use ($item) {
                    if ($suggestion->id == $item->id) {
                        return false;
                    }

                    // Restaurant-specific item ke liye same restaurant
                    if ($item->restaurant_id) {
                        return $suggestion->restaurant_id == $item->restaurant_id;
                    }

                    return true;
                })
                ->take(3);

            $sortOrder = 1;

            foreach ($suggestions as $suggestion) {
                $recommendations[] = [
                    'menu_item_id' => $item->id,
                    'recommended_menu_item_id' => $suggestion->id,
                    'sort_order' => $sortOrder++,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Duplicate records insert nahi honge
        DB::table('menu_item_recommendations')->upsert(
            $recommendations,
            ['menu_item_id', 'recommended_menu_item_id'],
            ['sort_order', 'status', 'updated_at']
        );

        $this->command->info(
            count($recommendations) . ' recommendation records processed successfully.'
        );
    }
}
