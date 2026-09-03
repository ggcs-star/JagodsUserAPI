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
use App\Http\Services\RestaurantTypeService;

class PopularRestaurantController extends BackendController
{
    use ApiResponse;
    protected $restaurantTypeService;
    public function __construct(RestaurantTypeService $restaurantTypeService)
    {
        parent::__construct();
        // $this->middleware('auth:api');
        $this->restaurantTypeService = $restaurantTypeService;
    }
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */


    public function index(Request $request)
    {
        try {

            $restroType = $this->restaurantTypeService
                ->getHeaderType($request);

            $perPage = (int) $request->get(
                'per_page',
                10
            );

            $page = (int) $request->get(
                'page',
                1
            );

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
                        ->module(
                            Module::YOUR_CITY_SLUG
                        )
                        ->where(
                            'status',
                            RestaurantStatus::ACTIVE
                        )
                        ->where(
                            'current_status',
                            CurrentStatus::YES
                        );

                    $query = $this->restaurantTypeService->apply(
                        $query,
                        $restroType,
                        'restroType'
                    );

                    $query->orderByDesc(
                        'total_orders'
                    );

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
                    'restro_type' =>
                    $request->header('restro_type'),

                    'message' =>
                    $e->getMessage(),

                    'file' =>
                    $e->getFile(),

                    'line' =>
                    $e->getLine(),
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
