<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Http\Services\MenuRecommendationService;
use Illuminate\Http\Request;
use App\Enums\Status;
use App\Traits\ApiResponse;
use App\Http\Resources\v1\MenuItemResource;
use Illuminate\Pagination\LengthAwarePaginator;

class MenuRecommendationController extends Controller
{
    use ApiResponse;

    public function index(Request $request, MenuRecommendationService $recommendationService)
    {
        $validated = $request->validate([
            'menu_item_ids' => ['required', 'array', 'min:1'],
            'menu_item_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $items = MenuItem::whereIn('id', $validated['menu_item_ids'])
            ->where('status', Status::ACTIVE)
            ->get();

        if ($items->isEmpty()) {
            return $this->notFoundResponse('No active menu items found.');
        }

        $allItemIds = $items->pluck('id')->map(fn($id) => (int) $id)->all();

        $products = $recommendationService->getBulkRecommendations($items, $allItemIds);

        $perPage = (int) ($validated['per_page'] ?? 10);
        $currentPage = (int) ($validated['page'] ?? 1);
        $total = $products->count();

        $paginatedProducts = $products->forPage($currentPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $paginatedProducts,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return $this->successPaginationResponse(
            'Recommendations retrieved successfully.',
            $paginator,
            MenuItemResource::collection($paginatedProducts)
        );
    }
}