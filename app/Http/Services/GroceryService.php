<?php

namespace App\Http\Services;

use App\Enums\Module;
use App\Enums\CategoryStatus;
use App\Enums\MenuItemStatus;
use App\Models\CategoryGroup;
use App\Models\Category;
use App\Models\MenuItem;

class GroceryService
{
    public function getCategoryGroups(int $perPage)
    {
        return CategoryGroup::with(['categories'])
            ->where('module_id', Module::ALL_OVER_INDIA)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    public function getMainCategories(int $moduleId, int $perPage)
    {
        $categories = Category::select(
            'id',
            'category_group_id',
            'name',
            'slug',
            'display_module_id',
            'sort_order'
        )
            ->with('categoryGroup:id,name')
            ->whereJsonContains('display_module_id', $moduleId)
            ->whereNull('parent_id')
            ->where('status', CategoryStatus::ACTIVE)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate($perPage);

        foreach ($categories as $category) {
            $categoryIds = Category::where('id', $category->id)
                ->orWhere('parent_id', $category->id)
                ->pluck('id');

            $category->items = $this->getCategoryTopItems($categoryIds);
        }

        return $categories;
    }

    public function getHomeCategories(int $perPage = 10)
    {
        $categories = Category::select(
            'id',
            'category_group_id',
            'name',
            'slug',
            'sort_order'
        )
            ->with('categoryGroup:id,name')
            ->where('module_id', Module::ALL_OVER_INDIA)
            ->whereNull('parent_id')
            ->where('status', CategoryStatus::ACTIVE)
            ->where('show_on_home', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        foreach ($categories as $category) {
            $categoryIds = Category::where('id', $category->id)
                ->orWhere('parent_id', $category->id)
                ->pluck('id');

            $category->items = $this->getCategoryTopItems($categoryIds);
        }

        return $categories;
    }

    private function getCategoryTopItems($categoryIds)
    {
        return MenuItem::with(['media', 'categories', 'variations', 'options'])
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->where('module_id', Module::ALL_OVER_INDIA)
            ->where('status', MenuItemStatus::ACTIVE)
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->limit(10)
            ->get();
    }

    public function getFilteredMenuItems(array $categoryIds, string $search, ?string $sortBy, int $perPage)
    {
        $query = MenuItem::with(['media', 'categories', 'variations', 'options'])
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->where('module_id', Module::ALL_OVER_INDIA)
            ->where('status', MenuItemStatus::ACTIVE);

        if ($search !== '') {
            $query = $this->applyFuzzyCategoryMenuSearch($query, $categoryIds, $search);
        }

        $this->applySorting($query, $sortBy);

        return $query->paginate($perPage);
    }

    private function applySorting($query, ?string $sortBy)
    {
        switch ($sortBy) {
            case 'popularity':
                $query->orderByDesc('counter');
                break;
            case 'new_arrivals':
                $query->orderByDesc('created_at');
                break;
            case 'price_low_high':
                $query->orderBy('unit_price', 'asc');
                break;
            case 'price_high_low':
                $query->orderBy('unit_price', 'desc');
                break;
            case 'discount_high_low':
                $query->orderByRaw("
                    CASE
                        WHEN unit_price > 0
                        THEN ((unit_price - discount_price) / unit_price) * 100
                        ELSE 0
                    END DESC
                ");
                break;
            default:
                $query->orderByDesc('updated_at');
                break;
        }
    }

    // ==========================================
    // FUZZY SEARCH LOGIC
    // ==========================================

    private function applyFuzzyCategoryMenuSearch($query, $categoryIds, string $search)
    {
        $search = strtolower(trim($search));

        $normalQuery = clone $query;
        $normalQuery->where(function ($q) use ($search) {
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
        });

        if ($normalQuery->exists()) {
            return $normalQuery;
        }

        $menuNames = MenuItem::query()
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->where('module_id', Module::ALL_OVER_INDIA)
            ->where('status', MenuItemStatus::ACTIVE)
            ->whereNotNull('name')
            ->select('name')
            ->distinct()
            ->pluck('name');

        $matchedNames = [];
        $searchSafe = substr($search, 0, 255);

        foreach ($menuNames as $menuName) {
            $menuNameLower = substr(strtolower(trim($menuName)), 0, 255);
            $bestScore = $this->calculateFuzzyScore($searchSafe, $menuNameLower);

            $words = preg_split('/\s+/', $menuNameLower);
            foreach ($words as $word) {
                if (strlen($word) < 3) continue;
                $wordScore = $this->calculateFuzzyScore($searchSafe, $word);
                $bestScore = max($bestScore, $wordScore);
            }

            if ($bestScore >= $this->getFuzzyThreshold($searchSafe)) {
                $matchedNames[] = [
                    'name' => $menuName,
                    'score' => $bestScore,
                ];
            }
        }

        usort($matchedNames, fn($a, $b) => $b['score'] <=> $a['score']);
        $matchedNames = array_slice($matchedNames, 0, 10);
        $names = array_column($matchedNames, 'name');

        if (empty($names)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('name', $names);
    }

    public function getCategoryMenuItemSuggestions($categoryIds, string $search): array
    {
        $search = strtolower(trim($search));
        if ($search === '') return [];

        $searchSafe = substr($search, 0, 255);

        $menuNames = MenuItem::query()
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->where('module_id', Module::ALL_OVER_INDIA)
            ->where('status', MenuItemStatus::ACTIVE)
            ->whereNotNull('name')
            ->select('name')
            ->distinct()
            ->pluck('name');

        $suggestions = [];

        foreach ($menuNames as $menuName) {
            $menuNameLower = substr(strtolower(trim($menuName)), 0, 255);
            $bestScore = $this->calculateFuzzyScore($searchSafe, $menuNameLower);

            $words = preg_split('/\s+/', $menuNameLower);
            foreach ($words as $word) {
                if (strlen($word) < 3) continue;
                $wordScore = $this->calculateFuzzyScore($searchSafe, $word);
                $bestScore = max($bestScore, $wordScore);
            }

            if ($bestScore >= $this->getFuzzyThreshold($searchSafe)) {
                $suggestions[] = [
                    'name' => $menuName,
                    'score' => $bestScore,
                ];
            }
        }

        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

        return collect(array_slice($suggestions, 0, 10))
            ->pluck('name')
            ->values()
            ->toArray();
    }

    private function calculateFuzzyScore(string $search, string $value): float
    {
        if ($search === '' || $value === '') return 0;
        if ($search === $value) return 100;
        if (str_contains($value, $search)) return 95;

        $distance = levenshtein($search, $value);
        $maxLength = max(strlen($search), strlen($value));

        if ($maxLength === 0) return 0;
        return (1 - ($distance / $maxLength)) * 100;
    }

    private function getFuzzyThreshold(string $search): int
    {
        $length = strlen($search);
        return match (true) {
            $length <= 2 => 80,
            $length === 3 => 70,
            $length === 4 => 65,
            $length >= 5 => 55,
            default => 60,
        };
    }
}
