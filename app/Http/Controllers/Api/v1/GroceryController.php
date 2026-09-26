<?php

namespace App\Http\Controllers\Api\v1;

use Throwable;
use App\Enums\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\BackendController;
use App\Traits\ApiResponse;
use App\Models\Category;
use Illuminate\Validation\ValidationException;
use App\Http\Services\GroceryService;

// Resources
use App\Http\Resources\v1\GroceryCategoryGroupResource;
use App\Http\Resources\v1\GroceryCategoryResource;
use App\Http\Resources\v1\GroceryCategoryDetailResource;
use App\Http\Resources\v1\MenuItemResource;
use App\Http\Resources\v1\GrocerySubCategoryResource;
use App\Http\Resources\v1\BannerResource;
use App\Enums\BannerStatus;
use App\Models\Banner;

class GroceryController extends BackendController
{
    use ApiResponse;

    protected $groceryService;

    // Dependency Injection
    public function __construct(GroceryService $groceryService)
    {
        $this->groceryService = $groceryService;
    }

    public function categoryGroups(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);

            $groups = $this->groceryService->getCategoryGroups($perPage);

            return $this->successPaginationResponse(
                message: 'Category groups fetched successfully.',
                paginator: $groups,
                data: GroceryCategoryGroupResource::collection($groups->items())
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Grocery Category API', 'Something went wrong while fetching category groups.');
        }
    }

    public function mainCategories(Request $request)
    {
        try {
            $request->validate([
                'module_id' => 'required|integer|exists:modules,id',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            $moduleId = (int) $request->input('module_id');
            $perPage = (int) $request->get('per_page', 10);

            $categories = $this->groceryService->getMainCategories($moduleId, $perPage);

            return $this->successPaginationResponse(
                message: 'Main categories fetched successfully.',
                paginator: $categories,
                data: GroceryCategoryResource::collection($categories->items())
            );
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed.', $e->errors(), 422);
        } catch (Throwable $e) {
            return $this->handleException($e, 'Main Category API', 'Something went wrong while fetching categories.');
        }
    }

    public function mainCategoriesItem(Request $request)
    {
        try {
            $request->validate([
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            $categories = $this->groceryService->getHomeCategories();

            return $this->successResponse(
                message: 'Main categories fetched successfully.',
                data: GroceryCategoryResource::collection($categories)
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Main Category API', 'Something went wrong while fetching categories.');
        }
    }

    public function categoryDetails(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|integer',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:100',
                'sort_by' => 'nullable|in:popularity,new_arrivals,price_low_high,price_high_low,discount_high_low',
            ]);

            $category = Category::with([
                'children' => function ($query) {
                    $query->select(
                        'id',
                        'parent_id',
                        'name',
                        'slug',
                        'sort_order'
                    )
                        ->orderBy('sort_order', 'asc')
                        ->orderBy('name', 'asc');
                }
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
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            $banners = Banner::query()
                ->where('status', BannerStatus::ACTIVE)
                ->where('target_type', 'category')
                ->where('target_id', $category->id)
                ->where('show_on_landing', 0)
                ->orderBy('sort', 'asc')
                ->get();
            // dd($banners);
            $response = $this->getMenuResponse(
                $category,
                $categoryIds,
                $request,
                GroceryCategoryDetailResource::class,
                'Category details fetched successfully.'
            );
            $responseData = $response->getData(true);
            $responseData['data']['data']['banners'] = BannerResource::collection($banners);
            // dd($responseData);
            return response()->json($responseData);
        } catch (ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (Throwable $e) {
            return $this->handleException(
                $e,
                'Category Details API',
                'Something went wrong.'
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
                'search' => 'nullable|string|max:100',
                'sort_by' => 'nullable|in:popularity,new_arrivals,price_low_high,price_high_low,discount_high_low',
            ]);

            $category = Category::select('id', 'name', 'slug', 'parent_id')
                ->where('module_id', Module::ALL_OVER_INDIA)
                ->whereNotNull('parent_id')
                ->where('id', $request->category_id)
                ->first();

            if (!$category) {
                return $this->notFoundResponse('Sub category not found.');
            }

            $categoryIds = [(int) $category->id];

            return $this->getMenuResponse(
                $category,
                $categoryIds,
                $request,
                GrocerySubCategoryResource::class,
                'Sub category details fetched successfully.'
            );
        } catch (ValidationException $e) {
            return $this->validationResponse($e->errors());
        } catch (Throwable $e) {
            return $this->handleException($e, 'Sub Category API', 'Something went wrong.');
        }
    }

    private function getMenuResponse($category, array $categoryIds, Request $request, $resourceClass, $message)
    {
        $perPage = (int) $request->input('per_page', 20);
        $search = trim($request->input('search', ''));
        $sortBy = $request->input('sort_by');

        $items = $this->groceryService->getFilteredMenuItems($categoryIds, $search, $sortBy, $perPage);

        $suggestions = $this->groceryService->getCategoryMenuItemSuggestions($categoryIds, $search);

        return $this->successPaginationResponse(
            message: $message,
            paginator: $items,
            data: [
                'category' => new $resourceClass($category),
                'suggestions' => $suggestions,
                'sort_by' => $this->getSortOptions(),
                'items' => MenuItemResource::collection($items->items()),
            ]
        );
    }

    private function getSortOptions(): array
    {
        return [
            ['key' => 'popularity', 'label' => 'Popularity'],
            ['key' => 'new_arrivals', 'label' => 'New Arrivals'],
            ['key' => 'price_low_high', 'label' => 'Price: Low to High'],
            ['key' => 'price_high_low', 'label' => 'Price: High to Low'],
            ['key' => 'discount_high_low', 'label' => 'Discount: High to Low'],
        ];
    }

    private function handleException(Throwable $e, string $logContext, string $defaultMessage)
    {
        Log::error($logContext, [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return $this->serverErrorResponse(
            message: config('app.debug') ? $e->getMessage() : $defaultMessage
        );
    }
}
