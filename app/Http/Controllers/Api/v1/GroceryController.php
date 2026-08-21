<?php

namespace App\Http\Controllers\Api\v1;

use Throwable;
use App\Enums\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\GroceryCategoryGroupResource;
use App\Models\CategoryGroup;
use App\Http\Controllers\BackendController;
use App\Traits\ApiResponse;
use App\Http\Resources\v1\GroceryCategoryResource;
use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Models\MenuItem;
use App\Enums\MenuItemStatus;
use App\Http\Resources\v1\GroceryCategoryDetailResource;
use App\Http\Resources\v1\MenuItemResource;
use App\Http\Resources\v1\GrocerySubCategoryResource;
class GroceryController extends BackendController
{
    use ApiResponse;
    public function categoryGroups(Request $request)
    {
        try {

            $perPage = (int) $request->get('per_page', 10);

            $groups = CategoryGroup::with([
                'categories'
            ])
                ->where('module_id', Module::ALL_OVER_INDIA)
                ->where('status', 1)
                ->orderBy('sort_order')
                ->paginate($perPage);

            return $this->successPaginationResponse(
                message: 'Category groups fetched successfully.',
                paginator: $groups,
                data: GroceryCategoryGroupResource::collection($groups->items())
            );

        } catch (Throwable $e) {

            Log::error('Grocery Category API', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Something went wrong while fetching category groups.'
            );
        }
    }

    public function mainCategories(Request $request)
    {
        try {

            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            $perPage = (int) $request->get('per_page', 10);

            $categories = Category::select(
                'id',
                'category_group_id',
                'name',
                'slug'
            )
                ->with('categoryGroup:id,name')
                ->where('module_id', Module::ALL_OVER_INDIA)
                ->whereNull('parent_id')
                ->where('status', CategoryStatus::ACTIVE)
                ->orderBy('name')
                ->paginate($perPage);

            foreach ($categories as $category) {

                $categoryIds = Category::where('id', $category->id)
                    ->orWhere('parent_id', $category->id)
                    ->pluck('id');

                $category->items = MenuItem::with([
                    'media',
                    'categories',
                    'variations',
                    'options',
                ])
                    ->whereHas('categories', function ($q) use ($categoryIds) {
                        $q->whereIn('categories.id', $categoryIds);
                    })
                    ->where('module_id', Module::ALL_OVER_INDIA)
                    ->where('status', MenuItemStatus::ACTIVE)
                    ->limit(10)
                    ->get();
            }

            return $this->successPaginationResponse(
                message: 'Main categories fetched successfully.',
                paginator: $categories,
                data: GroceryCategoryResource::collection($categories->items())
            );

        } catch (Throwable $e) {

            Log::error('Main Category API', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Something went wrong while fetching categories.'
            );
        }
    }
    public function mainCategoriesItem(Request $request)
    {
        try {

            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            $categories = Category::select(
                'id',
                'category_group_id',
                'name',
                'slug'
            )
                ->with('categoryGroup:id,name')
                ->where('module_id', Module::ALL_OVER_INDIA)
                ->whereNull('parent_id')
                ->where('status', CategoryStatus::ACTIVE)
                ->orderBy('name')
                ->limit(3)
                ->get();

            foreach ($categories as $category) {

                $categoryIds = Category::where('id', $category->id)
                    ->orWhere('parent_id', $category->id)
                    ->pluck('id');

                $category->items = MenuItem::with([
                    'media',
                    'categories',
                    'variations',
                    'options',
                ])
                    ->whereHas('categories', function ($q) use ($categoryIds) {
                        $q->whereIn('categories.id', $categoryIds);
                    })
                    ->where('module_id', Module::ALL_OVER_INDIA)
                    ->where('status', MenuItemStatus::ACTIVE)
                    ->limit(10)
                    ->get();
            }

            return $this->successResponse(
                message: 'Main categories fetched successfully.',
                data: GroceryCategoryResource::collection($categories)
            );

        } catch (Throwable $e) {

            Log::error('Main Category API', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Something went wrong while fetching categories.'
            );
        }
    }
    public function categoryDetails(Request $request)
    {
        try {

            $request->validate([
                'category_id' => 'required|integer',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|in:popularity,new_arrivals,price_low_high,price_high_low,discount_high_low',
            ]);

            $perPage = $request->input('per_page', 20);

            $category = Category::with([
                'children:id,parent_id,name,slug'
            ])
                ->where('id', $request->category_id)
                ->where('module_id', Module::ALL_OVER_INDIA)
                ->whereNull('parent_id')
                ->first();

            if (!$category) {
                return $this->notFoundResponse('Category not found.');
            }


            $categoryIds = Category::where('id', $category->id)
                ->orWhere('parent_id', $category->id)
                ->pluck('id');


            $query = MenuItem::with([
                'media',
                'categories',
                'variations',
                'options',
            ])
                ->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                })
                ->where('module_id', Module::ALL_OVER_INDIA)
                ->where('status', MenuItemStatus::ACTIVE);


            switch ($request->sort_by) {

                case 'popularity':
                    $query->orderByDesc('counter');
                    break;

                case 'new_arrivals':
                    $query->latest();
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
                    $query->latest();
                    break;
            }
            $items = $query->paginate($perPage);

            return $this->successPaginationResponse(
                message: 'Category details fetched successfully.',
                paginator: $items,
                data: [
                    'category' => new GroceryCategoryDetailResource($category),
                    'items' => MenuItemResource::collection($items->items()),
                ]
            );

        } catch (\Illuminate\Validation\ValidationException $e) {

            return $this->validationResponse($e->errors());

        } catch (\Throwable $e) {

            Log::error('Category Details API', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Something went wrong.'
            );
        }
    }

    public function subCategoryDetails(Request $request)
    {
        try {

            $request->validate([
                'category_id' => 'required|integer',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|in:popularity,new_arrivals,price_low_high,price_high_low,discount_high_low',
            ]);

            $perPage = $request->input('per_page', 20);


            $category = Category::select(
                'id',
                'name',
                'slug',
                'parent_id'
            )
                ->where('module_id', Module::ALL_OVER_INDIA)
                ->whereNotNull('parent_id')
                ->where('id', $request->category_id)
                ->first();

            if (!$category) {
                return $this->notFoundResponse('Sub category not found.');
            }


            $query = MenuItem::with([
                'media',
                'categories',
                'variations',
                'options',
            ])
                ->whereHas('categories', function ($q) use ($category) {
                    $q->where('categories.id', $category->id);
                })
                ->where('module_id', Module::ALL_OVER_INDIA)
                ->where('status', MenuItemStatus::ACTIVE);


            switch ($request->sort_by) {

                case 'popularity':
                    $query->orderByDesc('counter');
                    break;

                case 'new_arrivals':
                    $query->latest();
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
                    $query->latest();
                    break;
            }

            $items = $query->paginate($perPage);


            return $this->successPaginationResponse(
                message: 'Sub category details fetched successfully.',
                paginator: $items,
                data: [
                    'category' => new GrocerySubCategoryResource($category),
                    'items' => MenuItemResource::collection($items->items()),
                ]
            );

        } catch (\Illuminate\Validation\ValidationException $e) {

            return $this->validationResponse($e->errors());

        } catch (Throwable $e) {

            Log::error('Sub Category API', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Something went wrong.'
            );
        }
    }
}