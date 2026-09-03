<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\CurrentStatus;
use App\Enums\RestaurantStatus;
use App\Http\Resources\v1\PopularRestaurantResource;
use App\Models\Restaurant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\BackendController;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Facades\Cache;
use App\Enums\Module;

class PopularRestaurantController extends BackendController
{
    use ApiResponse;

    public function __construct()
    {
        parent::__construct();
        // $this->middleware('auth:api');

    }
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */


    public function index(Request $request)
    {
        try {

            $restroType = $request->get('restro_type');

            $perPage = (int) $request->get('per_page', 10);
            $page = (int) $request->get('page', 1);

            $cacheKey = "home_restaurants_{$restroType}_{$page}_{$perPage}";

            $ttl = now()->addMinutes(5);

            $restaurants = Cache::remember(
                $cacheKey,
                $ttl,
                function () use (
                    $restroType,
                    $perPage,
                    $page
                ) {

                    $query = Restaurant::select([
                        'id',
                        'name',
                        'slug',
                        'coverImg',
                        'opening_time',
                        'closing_time',
                        'restroType',
                        'sort_order',
                        'total_orders',
                        'description',
                        'address',
                        'avg_rating',
                        'total_reviews',
                    ])
                        ->module(Module::YOUR_CITY_SLUG)
                        ->where(
                            'status',
                            RestaurantStatus::ACTIVE
                        )
                        ->where(
                            'current_status',
                            CurrentStatus::YES
                        );
                    if (!blank($restroType)) {

                        $allowedTypes = [
                            'veg',
                            'non-veg',
                            'veg-and-non-veg',
                        ];

                        if (in_array($restroType, $allowedTypes, true)) {
                            $query->where(
                                'restroType',
                                $restroType
                            );
                        }
                    }

                    $query->orderByDesc('total_orders');

                    return $query->paginate(
                        $perPage,
                        ['*'],
                        'page',
                        $page
                    );
                }
            );

            return $this->successPaginationResponse(
                message: 'Restaurants fetched successfully.',
                paginator: $restaurants,
                data: PopularRestaurantResource::collection(
                    $restaurants->items()
                )
            );
        } catch (Throwable $e) {

            Log::error(
                'PopularRestaurant API Error',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->serverErrorResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Something went wrong while fetching restaurants.'
            );
        }
    }
}
