<?php

namespace App\Http\Services;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\Banner;
use App\Enums\Module;
use App\Enums\Status;
use App\Enums\BannerStatus;
use App\Enums\MenuItemStatus;
use App\Http\Resources\v1\MenuItemResource;
use App\Http\Resources\v1\PopularRestaurantResource;
use App\Http\Resources\v1\GroceryCategoryDetailResource;
use App\Http\Resources\v1\BannerResource;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ReelService
{
    public function getReels(
        Request $request,
        array $validated
    ): LengthAwarePaginator {
        $restaurantId = $validated['restaurant_id'] ?? null;
        $categoryId = $validated['category_id'] ?? null;

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);

        /*
         * 1. Smart Cache Shuffling logic for Pagination
         * Taaki Page 1 par random reels aayein aur scroll karne par duplicates na aayein.
         */
        $userIdentifier = $request->user() ? $request->user()->id : $request->ip();
        $cacheKey = 'reels_order_' . md5($userIdentifier . '_' . $restaurantId . '_' . $categoryId);

        // Agar user page 1 par hai, toh naya random order generate karke Cache mein daal do.
        if ($page === 1) {
            $filteredReels = $this->getFilteredAndShuffledReels($restaurantId, $categoryId);
            Cache::put($cacheKey, $filteredReels->all(), now()->addMinutes(30));
        } else {
            // Agar page > 1 hai, toh Cache se purana order uthao taaki duplicates na aayein.
            $cachedReels = Cache::get($cacheKey);
            if ($cachedReels) {
                $filteredReels = collect($cachedReels);
            } else {
                // Fallback agar cache expire ho jaye
                $filteredReels = $this->getFilteredAndShuffledReels($restaurantId, $categoryId);
                Cache::put($cacheKey, $filteredReels->all(), now()->addMinutes(30));
            }
        }

        $total = $filteredReels->count();

        /*
         * 2. Slice items for the current page only (Reduces Server Load hugely)
         */
        $reels = $filteredReels
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        $restaurantIds = $this->getUniqueIds($reels, 'restaurant_id');
        $categoryIds = $this->getUniqueIds($reels, 'category_id');

        /*
         * 3. Fetch restaurant, category details (with children) and banners in batches.
         */
        $restaurants = $restaurantIds->isEmpty()
            ? collect()
            : Restaurant::query()
                ->whereIn('id', $restaurantIds->all())
                ->get()
                ->keyBy(fn ($item) => (int) $item->getKey());

        $categories = $categoryIds->isEmpty()
            ? collect()
            : Category::query()
                ->with([
                    'children' => function ($query) {
                        $query->select('id', 'parent_id', 'name', 'slug', 'sort_order')
                            ->where('status', Status::ACTIVE)
                            ->orderBy('sort_order', 'asc')
                            ->orderBy('name', 'asc');
                    }
                ])
                ->whereIn('id', $categoryIds->all())
                ->get()
                ->keyBy(fn ($item) => (int) $item->getKey());

        $banners = $categoryIds->isEmpty()
            ? collect()
            : Banner::query()
                ->where('status', BannerStatus::ACTIVE)
                ->where('target_type', 'category')
                ->whereIn('target_id', $categoryIds->all())
                ->where('show_on_landing', 0)
                ->orderBy('sort', 'asc')
                ->get()
                ->groupBy('target_id');

        /*
         * 4. Fetch up to 4 active items per restaurant.
         */
        $restaurantItemRows = collect();

        if ($restaurantIds->isNotEmpty()) {
            $rankedItems = MenuItem::query()
                ->select(['menu_items.id', 'menu_items.restaurant_id'])
                ->selectRaw(
                    'ROW_NUMBER() OVER (
                        PARTITION BY menu_items.restaurant_id
                        ORDER BY menu_items.counter DESC, menu_items.id DESC
                    ) AS row_num'
                )
                ->whereIn('menu_items.restaurant_id', $restaurantIds->all())
                ->where('menu_items.module_id', Module::YOUR_CITY)
                ->where('menu_items.status', MenuItemStatus::ACTIVE);

            $restaurantItemRows = DB::query()
                ->fromSub($rankedItems, 'ranked')
                ->where('row_num', '<=', 4)
                ->get(['id', 'restaurant_id']);
        }

        /*
         * 5. Fetch up to 4 active items per category.
         */
        $categoryItemRows = collect();

        if ($categoryIds->isNotEmpty()) {
            $rankedItems = MenuItem::query()
                ->join('category_menu_items as cmi', 'cmi.menu_item_id', '=', 'menu_items.id')
                ->select(['menu_items.id', 'cmi.category_id'])
                ->selectRaw(
                    'ROW_NUMBER() OVER (
                        PARTITION BY cmi.category_id
                        ORDER BY menu_items.counter DESC, menu_items.id DESC
                    ) AS row_num'
                )
                ->whereIn('cmi.category_id', $categoryIds->all())
                ->where('menu_items.status', MenuItemStatus::ACTIVE);

            $categoryItemRows = DB::query()
                ->fromSub($rankedItems, 'ranked')
                ->where('row_num', '<=', 4)
                ->get(['id', 'category_id']);
        }

        /*
         * 6. Fetch preview items together (with relationships for resource).
         */
        $previewItemIds = $restaurantItemRows
            ->pluck('id')
            ->merge($categoryItemRows->pluck('id'))
            ->unique()
            ->values()
            ->all();

        $menuItems = empty($previewItemIds)
            ? collect()
            : MenuItem::query()
                ->with(['categories', 'variationGroups', 'optionGroups', 'media'])
                ->whereKey($previewItemIds)
                ->where('status', MenuItemStatus::ACTIVE)
                ->get()
                ->keyBy(fn ($item) => (int) $item->getKey());

        /*
         * 7. Format item preview using MenuItemResource.
         */
        $formatItem = function ($id) use ($menuItems, $request) {
            $item = $menuItems->get((int) $id);
            if (!$item) return null;
            return (new MenuItemResource($item))->resolve($request);
        };

        $restaurantItems = $restaurantItemRows
            ->groupBy('restaurant_id')
            ->map(function ($rows) use ($formatItem) {
                return $rows->map(fn ($row) => $formatItem($row->id))->filter()->values();
            });

        $categoryItems = $categoryItemRows
            ->groupBy('category_id')
            ->map(function ($rows) use ($formatItem) {
                return $rows->map(fn ($row) => $formatItem($row->id))->filter()->values();
            });

        /*
         * 8. Attach restaurant/category details to each reel.
         */
        $data = $reels->map(function (array $reel) use (
            $request,
            $restaurants,
            $categories,
            $banners,
            $restaurantItems,
            $categoryItems
        ) {
            $restaurantId = isset($reel['restaurant_id']) && $reel['restaurant_id'] !== ''
                ? (int) $reel['restaurant_id'] : null;

            $categoryId = isset($reel['category_id']) && $reel['category_id'] !== ''
                ? (int) $reel['category_id'] : null;

            $reel['restaurant_id'] = $restaurantId;
            $reel['category_id'] = $categoryId;
            $reel['restaurant'] = null;
            $reel['category'] = null;
            $reel['items_preview'] = [];

            if ($restaurantId !== null) {
                $restaurant = $restaurants->get($restaurantId);
                if ($restaurant) {
                    $reel['restaurant'] = (new PopularRestaurantResource($restaurant))->resolve($request);
                }
                $reel['items_preview'] = ($restaurantItems->get($restaurantId) ?? collect())->values()->all();
            } elseif ($categoryId !== null) {
                $category = $categories->get($categoryId);
                if ($category) {
                    $categoryData = (new GroceryCategoryDetailResource($category))->resolve($request);
                    $categoryBanners = $banners->get($categoryId) ?? collect();
                    $categoryData['banners'] = BannerResource::collection($categoryBanners)->resolve($request);
                    $reel['category'] = $categoryData;
                }
                $reel['items_preview'] = ($categoryItems->get($categoryId) ?? collect())->values()->all();
            }

            /*
             * Convert relative video path to absolute URL.
             */
            $videoUrl = $reel['videoUrl'] ?? '';
            if ($videoUrl !== '' && !preg_match('/^https?:\/\//i', $videoUrl)) {
                $reel['videoUrl'] = asset(ltrim($videoUrl, '/'));
            }

            return $reel;
        })->values();

        /*
         * 9. Return a Laravel paginator.
         */
        return new LengthAwarePaginator(
            $data->all(),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * Filter config reels and shuffle them uniquely.
     */
    private function getFilteredAndShuffledReels($restaurantId, $categoryId): Collection
    {
        return collect(config('reel', []))
            ->filter(function (array $reel) use ($restaurantId, $categoryId) {
                if ($restaurantId !== null) {
                    return (int) ($reel['restaurant_id'] ?? 0) === (int) $restaurantId;
                }
                if ($categoryId !== null) {
                    return (int) ($reel['category_id'] ?? 0) === (int) $categoryId;
                }
                return true;
            })
            ->shuffle() // Har nayi request par random order set karega
            ->values();
    }

    private function getUniqueIds(Collection $reels, string $column): Collection
    {
        return $reels
            ->pluck($column)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}