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

class BusinessSettingController extends Controller
{
    public function __construct(
        private ShiprocketService $shiprocketService
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $request->validate([
                'pincode' => [
                    'required',
                    'string',
                    'size:6',
                    'regex:/^[0-9]{6}$/',
                ],

                'total_qty' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'cod' => [
                    'nullable',
                    'boolean',
                ],
            ]);

            $deliveryPincode = trim(
                $request->input('pincode')
            );

            $totalQty = (int) $request->input(
                'total_qty'
            );

            $cod = (int) $request->input(
                'cod',
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Weight Calculation
            |--------------------------------------------------------------------------
            |
            | 1 Quantity = 1 KG
            |
            */
            $weight = $totalQty * 1;

            /*
            |--------------------------------------------------------------------------
            | Business Settings
            |--------------------------------------------------------------------------
            */
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

            /*
            |--------------------------------------------------------------------------
            | Default Address
            |--------------------------------------------------------------------------
            */
            $defaultAddress = Address::query()
                ->where('user_id', auth()->id())
                ->where('is_default', 1)
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Shiprocket
            |--------------------------------------------------------------------------
            */
            $delivery = $this->shiprocketService->getDeliveryRate(
                deliveryPincode: $deliveryPincode,
                weight: $weight,
                cod: $cod
            );

            /*
            |--------------------------------------------------------------------------
            | Business Settings Resource
            |--------------------------------------------------------------------------
            */
            $businessSettings = (new BusinessSettingResource($settings))
                ->toArray($request);

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */
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