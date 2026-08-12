<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Http\Resources\v1\BusinessSettingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BusinessSettingController extends Controller
{
    public function index(): JsonResponse
    {
        try {

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

            $data = (new BusinessSettingResource($settings))
                ->toArray(request());

            return response()->json([
                'status' => true,
                'success' => true,
                'status_code' => 200,
                'errors' => [],
                'message' => 'Business settings fetched successfully.',
                'data' => $data,
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Business Settings API Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'success' => false,
                'status_code' => 500,
                'errors' => [],
                'message' => 'Unable to fetch business settings.',
                'data' => [],
            ], 500);
        }
    }
}