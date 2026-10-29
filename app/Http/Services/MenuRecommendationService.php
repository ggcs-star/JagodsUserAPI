<?php

namespace App\Http\Services;

use App\Models\MenuItem;
use App\Models\MenuItemRecommendation;
use App\Enums\Status;
use Illuminate\Support\Collection;

class MenuRecommendationService
{
    public function getBulkRecommendations(Collection $cartItems, array $cartItemIds): Collection 
    {
        $moduleIds = $cartItems->pluck('module_id')->unique()->filter()->toArray();

        return MenuItemRecommendation::query()
            ->whereIn('menu_item_id', $cartItemIds) 
            ->where('status', 1)
            ->whereHas('recommendedItem', function ($query) use ($cartItemIds, $moduleIds) {
                
                $query->where('status', Status::ACTIVE);
                
                $query->whereNotIn('id', $cartItemIds); 

                if (!empty($moduleIds)) {
                    $query->whereIn('module_id', $moduleIds);
                }
            })
            ->with('recommendedItem')
            ->orderBy('sort_order')
            ->get()
            ->pluck('recommendedItem')
            ->filter()
            ->unique('id') 
            ->values();
    }
}