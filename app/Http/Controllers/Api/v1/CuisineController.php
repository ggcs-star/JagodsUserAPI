<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BackendController;
use App\Http\Requests\Api\CuisineRequest;
use App\Http\Resources\v1\CuisineResource;
use App\Http\Resources\v1\PopularRestaurantResource;
use App\Http\Resources\v1\RestaurantResource;
use App\Http\Services\CuisineService;
use App\Models\Cuisine;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Services\RestaurantTypeService;

class CuisineController extends BackendController
{
    use ApiResponse;
    protected $cuisineService;
    protected $restaurantTypeService;
    public function __construct(CuisineService $cuisineService, RestaurantTypeService $restaurantTypeService)
    {
        parent::__construct();
        // $this->middleware('auth:api');
        $this->cuisineService = $cuisineService;
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

            $cuisines = $this->cuisineService->allCuisines($request);

            return $this->successPaginationResponse(
                message: 'Cuisine list fetched successfully.',
                paginator: $cuisines,
                data: CuisineResource::collection($cuisines->items())
            );
        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Internal Server Error'
            );
        }
    }

    public function show(Request $request)
    {
        try {

            $id = $request->input('id');

            $cuisine = $this->cuisineService->show($id);

            $restroType = $this->restaurantTypeService
                ->getHeaderType($request);


            $allowedTypes = $this->restaurantTypeService
                ->getAllowedTypes($restroType);

            $activeRestaurants = $cuisine->restaurants()
                ->where(
                    'restaurants.status',
                    \App\Enums\RestaurantStatus::ACTIVE
                )
                ->when(
                    $allowedTypes !== null,
                    function ($query) use ($allowedTypes) {

                        $query->whereIn(
                            'restaurants.restroType',
                            $allowedTypes
                        );
                    }
                )
                ->get();

            $this->data['cuisine'] =
                new CuisineResource($cuisine);

            $this->data['restaurants'] =
                PopularRestaurantResource::collection(
                    $activeRestaurants
                );

            return $this->successResponse(
                message: 'Cuisine details fetched successfully',
                data: $this->data
            );
        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Internal Server Error'
            );
        }
    }
}
