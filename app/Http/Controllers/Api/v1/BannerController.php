<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\BannerStatus;
use App\Http\Controllers\BackendController;
use App\Http\Requests\BannerRequest;
use App\Http\Resources\v1\BannerResource;
use App\Traits\ApiResponse;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Restaurant;
use App\Http\Services\RestaurantTypeService;
use App\Http\Resources\v1\HomeAdResource;

class BannerController extends BackendController
{
    use ApiResponse;

    protected RestaurantTypeService $restaurantTypeService;

    public function __construct(
        RestaurantTypeService $restaurantTypeService
    ) {
        $this->restaurantTypeService = $restaurantTypeService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function index()
    // {
    //     $banners= Banner::where('status',BannerStatus::ACTIVE)->orderBy('sort', 'asc')->get();
    //     return $this->successresponse(['success'=>200, 'data'=> BannerResource::collection($banners)]);
    // }

    public function index(Request $request)
    {
        $request->validate([
            'module_id' => [
                'required',
                'integer',
                'in:1,2',
            ],
        ]);
        try {

            $moduleId = (int) $request->module_id;

            $restroType = $this->restaurantTypeService
                ->getHeaderType($request);

            $allowedTypes = $this->restaurantTypeService
                ->getAllowedTypes($restroType);


            $banners = Banner::query()
                ->where('status', BannerStatus::ACTIVE)
                ->where('show_on_landing', 1)

                ->when(
                    $moduleId === 1,
                    function ($query) use ($allowedTypes) {

                        // Restaurant banners
                        $query->where('target_type', 'restaurant')

                            ->when(
                                $allowedTypes !== null,
                                function ($query) use ($allowedTypes) {

                                    $query->whereHasMorph(
                                        'target',
                                        [Restaurant::class],
                                        function ($restaurantQuery) use ($allowedTypes) {

                                            $restaurantQuery->whereIn(
                                                'restroType',
                                                $allowedTypes
                                            );
                                        }
                                    );
                                }
                            );
                    }
                )

                ->when(
                    $moduleId === 2,
                    function ($query) {

                        // Category banners
                        $query->where('target_type', 'category');
                    }
                )
                ->orderBy('sort', 'asc')
                ->get();
            return $this->successResponse(
                message: 'Banner list fetched successfully.',
                data: [

                    'banners' => BannerResource::collection($banners),
                ]
            );
        } catch (\Throwable $e) {

            Log::error(
                'Banner API Error',
                [
                    'module_id' => $request->module_id,
                    'restro_type' => $request->header('restro_type'),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return $this->serverErrorResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Internal Server Error'
            );
        }
    }

    public function getHomeAds()
    {
        $ads = [
            [
                'id' => 1,

                'title' => 'Summer Sale',

                'subtitle' => 'Unlock exclusive food vouchers on Cloud Food Court and use them while ordering on Jagods',

                'media_type' => 'banner',

                'media_url' => 'https://images.jagods.in/landing/Rectangle%2060023%402x.png',

                'redirect_url' => 'https://jagods.com',

                'theme_color' => null,

                'placement' => 'home_popup',

                'priority' => 1,
            ],

            [
                'id' => 2,

                'title' => 'App Promo',

                'subtitle' => null,

                'media_type' => 'banner',

                'cta' => [],

                'offers' => [],

                'media_url' => 'https://images.jagods.in/landing/Group%20191222.svg',

                'redirect_url' => 'https://jagods.com',

                'theme_color' => null,

                'placement' => 'restaurant_middle',

                'priority' => 2,
            ],

        ];

        return $this->successResponse(
            message: 'Home ads fetched successfully.',
            data: [
                'ads' => HomeAdResource::collection($ads),
            ]
        );
    }
}
