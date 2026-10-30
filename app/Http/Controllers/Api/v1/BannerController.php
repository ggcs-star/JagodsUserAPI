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
        $validated = $request->validate([
            'module_id' => [
                'required',
                'integer',
                'exists:modules,id',
            ],
        ]);

        try {

            $moduleId = (int) $validated['module_id'];

            $restroType = $this->restaurantTypeService
                ->getHeaderType($request);

            $allowedTypes = $this->restaurantTypeService
                ->getAllowedTypes($restroType);

            $banners = Banner::query()
                ->where('module_id', $moduleId)
                ->where('status', BannerStatus::ACTIVE)
                ->where('show_on_landing', 1)

                ->when(
                    $moduleId === 1,
                    function ($query) use ($allowedTypes) {

                        $query->where(function ($targetQuery) use ($allowedTypes) {

                            $targetQuery->where('target_type', 'category')

                                ->orWhere(function ($restaurantQuery) use ($allowedTypes) {

                                    $restaurantQuery->where('target_type', 'restaurant')

                                        ->when(
                                            $allowedTypes !== null,
                                            function ($restaurantQuery) use ($allowedTypes) {

                                                $restaurantQuery->whereHasMorph(
                                                    'target',
                                                    [Restaurant::class],
                                                    function ($q) use ($allowedTypes) {
                                                        $q->whereIn(
                                                            'restroType',
                                                            $allowedTypes
                                                        );
                                                    }
                                                );
                                            }
                                        );
                                });
                        });
                    }
                )

                ->when(
                    $moduleId === 2,
                    function ($query) {

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

            Log::error('Banner API Error', [
                'module_id' => $request->module_id,
                'restro_type' => $request->header('restro_type'),
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
