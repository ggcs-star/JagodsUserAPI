<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\AddressResource;
use App\Http\Resources\v1\BusinessSettingResource;
use App\Http\Services\ShiprocketService;
use App\Models\Address;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\v1\HomeAdResource;

class BusinessSettingController extends Controller
{
    public function __construct(
        private ShiprocketService $shiprocketService
    ) {
        // $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $request->validate([

                'pincode' => [
                    'nullable',
                    'string',
                    'size:6',
                    'regex:/^[0-9]{6}$/',
                ],

                'total_qty' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'required_with:pincode',
                ],

                'cod' => [
                    'nullable',
                    'boolean',
                ],
            ]);

            $deliveryPincode = $request->filled('pincode')
                ? trim($request->input('pincode'))
                : null;

            $totalQty = $request->filled('total_qty')
                ? (int) $request->input('total_qty')
                : null;

            $cod = (int) $request->input('cod', 0);

            $weight = $totalQty !== null
                ? $totalQty * 1
                : null;


            $settings = Setting::whereNotIn('key', [
                'purchase_code',
                'purchase_username',
                'razorpay_secret',
                'stripe_secret',
                'paystack_secret',
                'smtp_password',
                'mail_password',
                'firebase_private_key',
                'aws_secret',
            ])
                ->pluck('value', 'key');
            $defaultAddress = Address::query()
                ->where('user_id', auth()->id())
                ->where('is_default', 1)
                ->first();

            $delivery = null;

            if ($deliveryPincode !== null) {

                $delivery = $this->shiprocketService->getDeliveryRate(
                    deliveryPincode: $deliveryPincode,
                    weight: $weight,
                    cod: $cod
                );
            }
            $home_popup = [
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

            ];
            $restaurant_middle = [

                [
                    'id' => 1,

                    'title' => 'Save More on Every Meal!',

                    'subtitle' => 'Unlock exclusive food vouchers on Cloud Food Court and use them while ordering on Jagods',

                    'media_type' => 'banner',

                    'card' => [

                        'background_image' => 'https://images.jagods.in/landing/Group%20191222.svg',

                        'icon' => 'https://images.jagods.in/landing/Group%20191149@2x.png',

                        'title' => 'Save More on Every Meal!',

                        'button' => [
                            'text' => 'Install & Save Now',
                            'link' => 'https://jagods.com',
                        ],

                        'know_more' => [
                            'text' => 'Know More',
                        ],

                        'details' => [

                            'background_image' => 'https://images.jagods.in/landing/1030404_OKA5BX1_(1).svg',

                            'icon' => 'https://images.jagods.in/landing/Group%20191149@2x.png',

                            'title' => 'Save More on Every Meal!',

                            'subtitle' => 'Unlock exclusive food vouchers on Cloud Food Court and use them while ordering on Jagods',

                            'button' => [
                                'text' => 'Install & Save Now',
                                'link' => 'https://jagods.com',
                            ],

                            'offers' => [

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Flat ₹50–₹200 OFF',
                                    'subtitle' => 'on your first food order',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Daily Combo Offers',
                                    'subtitle' => 'on top restaurants',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Use Online & In-Store',
                                    'subtitle' => 'at selected food courts',
                                ],
                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Flat ₹50–₹200 OFF',
                                    'subtitle' => 'on your first food order',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Daily Combo Offers',
                                    'subtitle' => 'on top restaurants',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Use Online & In-Store',
                                    'subtitle' => 'at selected food courts',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Flat ₹50–₹200 OFF',
                                    'subtitle' => 'on your first food order',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Daily Combo Offers',
                                    'subtitle' => 'on top restaurants',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Use Online & In-Store',
                                    'subtitle' => 'at selected food courts',
                                ],
                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Flat ₹50–₹200 OFF',
                                    'subtitle' => 'on your first food order',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Daily Combo Offers',
                                    'subtitle' => 'on top restaurants',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Use Online & In-Store',
                                    'subtitle' => 'at selected food courts',
                                ],

                            ],
                        ],
                    ],
                ],

            ];
            $all_india_middle = [

                [
                    'id' => 1,

                    'title' => 'Save More on Every Meal!',

                    'subtitle' => 'Unlock exclusive food vouchers on Cloud Food Court and use them while ordering on Jagods',

                    'media_type' => 'banner',

                    'card' => [

                        'background_image' => 'https://images.jagods.in/landing/Group%20191222.svg',

                        'icon' => 'https://images.jagods.in/landing/Group%20191149@2x.png',

                        'title' => 'Save More on Every Meal!',

                        'button' => [
                            'text' => 'Install & Save Now',
                            'link' => 'https://jagods.com',
                        ],

                        'know_more' => [
                            'text' => 'Know More',
                        ],

                        'details' => [

                            'background_image' => 'https://images.jagods.in/landing/1030404_OKA5BX1_(1).svg',

                            'icon' => 'https://images.jagods.in/landing/Group%20191149@2x.png',

                            'title' => 'Save More on Every Meal!',

                            'subtitle' => 'Unlock exclusive food vouchers on Cloud Food Court and use them while ordering on Jagods',

                            'button' => [
                                'text' => 'Install & Save Now',
                                'link' => 'https://jagods.com',
                            ],

                            'offers' => [

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Flat ₹50–₹200 OFF',
                                    'subtitle' => 'on your first food order',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Daily Combo Offers',
                                    'subtitle' => 'on top restaurants',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Use Online & In-Store',
                                    'subtitle' => 'at selected food courts',
                                ],
                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Flat ₹50–₹200 OFF',
                                    'subtitle' => 'on your first food order',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Daily Combo Offers',
                                    'subtitle' => 'on top restaurants',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Use Online & In-Store',
                                    'subtitle' => 'at selected food courts',
                                ],
                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Flat ₹50–₹200 OFF',
                                    'subtitle' => 'on your first food order',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Daily Combo Offers',
                                    'subtitle' => 'on top restaurants',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Use Online & In-Store',
                                    'subtitle' => 'at selected food courts',
                                ],
                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Flat ₹50–₹200 OFF',
                                    'subtitle' => 'on your first food order',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Daily Combo Offers',
                                    'subtitle' => 'on top restaurants',
                                ],

                                [
                                    'icon' => 'https://images.jagods.in/landing/easy-to-use.svg',
                                    'title' => 'Use Online & In-Store',
                                    'subtitle' => 'at selected food courts',
                                ],

                            ],
                        ],
                    ],
                ],


            ];
            $businessSettings = (new BusinessSettingResource($settings))
                ->toArray($request);

            return response()->json([
                'status' => true,
                'success' => true,
                'status_code' => 200,
                'errors' => [],
                'message' => 'Business settings fetched successfully.',

                'data' => [

                    'settings' => $businessSettings,

                    'default_address' => $defaultAddress
                        ? new AddressResource($defaultAddress)
                        : null,

                    'order_summary' => [
                        'pincode' => $deliveryPincode,
                        'total_qty' => $totalQty,
                        'weight_kg' => $weight,
                        'cod' => $cod === 1,
                    ],

                    'all_india_delivery' => $delivery,
                    'home_popup' => $home_popup,
                    'restaurant_middle' => HomeAdResource::collection($restaurant_middle),
                    'all_india_middle' => HomeAdResource::collection($all_india_middle),
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status' => false,
                'success' => false,
                'status_code' => 422,
                'errors' => $e->errors(),
                'message' => 'Validation Error',
                'data' => [],
            ], 422);
        } catch (\Throwable $e) {

            Log::error('Business Settings API Error', [
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'success' => false,
                'status_code' => 500,
                'errors' => [],
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Unable to fetch business settings.',
                'data' => [],
            ], 500);
        }
    }
}
