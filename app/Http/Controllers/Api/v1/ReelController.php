<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Services\ReelService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReelController extends Controller
{
    use ApiResponse;

    public function index(
        Request $request,
        ReelService $reelService
    ) {
        $validated = $request->validate([
            'restaurant_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:20',
            ],
        ]);

        if (
            ($validated['restaurant_id'] ?? null) !== null
            && ($validated['category_id'] ?? null) !== null
        ) {
            return $this->errorResponse(
                message: 'Provide either restaurant_id or category_id, not both.',
                statusCode: 422
            );
        }

        try {
            $reels = $reelService->getReels(
                request: $request,
                validated: $validated
            );

            return $this->successPaginationResponse(
                message: 'Reels retrieved successfully.',
                paginator: $reels,
                data: $reels->items()
            );
        } catch (Throwable $e) {
            Log::error('Reel API Error', [
                'restaurant_id' => $request->input('restaurant_id'),
                'category_id' => $request->input('category_id'),
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 10),
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverErrorResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Internal Server Error'
            );
        }
    }
}