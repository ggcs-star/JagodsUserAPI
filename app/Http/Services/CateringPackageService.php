<?php

namespace App\Http\Services;

use App\Models\CateringPackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\CateringPackageSection;
use App\Models\Category;
use App\Enums\Module;
use App\Enums\CategoryStatus;
class CateringPackageService
{
    public function getPackages(
        int $perPage = 20,
        ?int $categoryId = null
    ): LengthAwarePaginator {
        return CateringPackage::query()
            ->where('status', 1)

            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })

            ->with([
                'category',
                'activeSections.activeItems.menuItem',
            ])

            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }
    public function getCateringCategories()
{
    return Category::query()
        ->where('module_id', Module::JAGDAI_CATERING)
        ->where('status', CategoryStatus::ACTIVE)
        ->orderBy('sort_order', 'asc')
        ->orderBy('name', 'asc')
        ->get();
}

    public function getPackageDetails(int $id): CateringPackage
    {
        return CateringPackage::query()
            ->where('status', 1)
            ->with([
                'activeSections.activeItems.menuItem',
            ])
            ->findOrFail($id);
    }

    public function getPackageSections(int $packageId)
    {
        return CateringPackageSection::query()
            ->where('catering_package_id', $packageId)
            ->where('status', 1)
            ->withCount([
                'activeItems as items_count',
            ])
            ->orderBy('sort_order', 'asc')
            ->get();
    }
    public function getSectionDetails(int $sectionId): CateringPackageSection
    {
        return CateringPackageSection::query()
            ->where('status', 1)
            ->with([
                'activeItems.menuItem',
                'activeItems.menuItem.variationGroups',
                'activeItems.menuItem.optionGroups',
                'activeItems.menuItem.categories',
            ])
            ->findOrFail($sectionId);
    }
}
