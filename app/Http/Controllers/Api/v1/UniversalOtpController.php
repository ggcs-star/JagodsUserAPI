<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Services\OtpService;
use App\Models\User;
use App\Http\Services\Auth\AuthLoginService;
use App\Enums\UserRole;
use App\Http\Resources\v1\RestaurantResource;
use App\Http\Resources\v1\PrivateUserResource;
use App\Http\Resources\v1\MeResource;
use App\Http\Services\DeviceIdentificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use App\Traits\ApiResponse;

class UniversalOtpController extends Controller
{
    use ApiResponse;

    protected $otpService;
    protected $authLoginService;
    protected $deviceService;

    public function __construct(
        OtpService $otpService,
        AuthLoginService $authLoginService,
        DeviceIdentificationService $deviceService
    ) {
        $this->otpService = $otpService;
        $this->authLoginService = $authLoginService;
        $this->deviceService = $deviceService;
    }

    public function send(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'purpose' => 'required|in:login,device_verification,forgot_password,profile_update',
        ]);

        if ($request->purpose === 'profile_update') {
            $sessionData = Cache::get("profile_update_{$request->temp_token}");
        } else {
            $sessionData = Cache::get("otp_session_{$request->temp_token}");
        }

        if (!$sessionData) {
            return $this->errorResponse(
                message: 'OTP session expired.',
                statusCode: 400
            );
        }

        if ($request->purpose === 'profile_update') {
            $currentUser = auth('api')->user();

            if (!$currentUser) {
                return $this->unauthorizedResponse('Unauthorized access.');
            }

            $user = clone $currentUser;
            $user->email = $sessionData['email'] ?? $user->email;
            $user->phone = $sessionData['phone'] ?? $user->phone;
        } else {
            $user = $this->findUser($sessionData['email_or_phone'] ?? null);
        }

        if (!$user) {
            return $this->notFoundResponse('User not found.');
        }

        $deviceId = resolveDeviceId($request);

        $result = $this->otpService->generateAndSend(
            $user,
            $request->purpose,
            $deviceId,
            $request->ip()
        );

        if (!$result['status']) {
            return $this->errorResponse(
                message: $result['message'],
                statusCode: $result['code'] ?? 400
            );
        }

        return $this->successResponse(
            message: 'OTP resent successfully.',
            data: [
                'temp_token' => $request->temp_token,
                'purpose' => $request->purpose,
                'expires_in' => $result['expires_in'] ?? 600,
            ]
        );
    }

    public function verify(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'otp' => 'required|numeric',
        ]);

        $emailOrPhone = null;
        $purpose = null;
        $isOtpSession = false;
        $sessionData = null;

        // 👇 FIX: Verify me bhi pehle profile_update cache check karenge taaki full details milen
        $profileSession = Cache::get("profile_update_{$request->temp_token}");

        if ($profileSession) {
            $sessionData = $profileSession;
            $purpose = 'profile_update';
            $isOtpSession = false;
        } else {
            $otpSession = Cache::get("otp_session_{$request->temp_token}");
            if ($otpSession) {
                $sessionData = $otpSession;
                $emailOrPhone = $sessionData['email_or_phone'] ?? null;
                $purpose = $sessionData['purpose'] ?? null;
                $isOtpSession = true;
            }
        }

        if (!$sessionData || !$purpose) {
            return $this->errorResponse('Session expired or missing details. Please provide a valid temp_token.', 400);
        }

        if ($purpose === 'profile_update') {
            $currentUser = auth('api')->user();
            if (!$currentUser) {
                return $this->unauthorizedResponse('Unauthorized access.');
            }

            $user = clone $currentUser;
            // Naya email/phone inject kar rahe hain OTP verify hone ke liye
            $user->email = $sessionData['email'] ?? $user->email;
            $user->phone = $sessionData['phone'] ?? $user->phone;
        } else {
            $user = $this->findUser($emailOrPhone);
        }

        if (!$user) {
            return $this->notFoundResponse('User not found.');
        }
        if ($purpose === 'forgot_password') {

            if (($sessionData['device_id'] ?? null) !== resolveDeviceId($request)) {

                return $this->forbiddenResponse(
                    'Security mismatch. OTP must be verified from the same device.'
                );
            }
        }
        $deviceId = resolveDeviceId($request);

        $verification = $this->otpService->verify(
            $user,
            $purpose,
            $request->otp,
            $deviceId,
            $request->ip()
        );

        if (!$verification['status']) {
            return $this->errorResponse($verification['message'], 400);
        }

        if ($isOtpSession) {
            Cache::forget("otp_session_{$request->temp_token}");
        }

        switch ($purpose) {
            case 'login':
            case 'device_verification':
                return $this->handleSuccessfulLogin($user, $request);

            case 'forgot_password':
                $resetToken = Str::random(80);

                // 1. Generate Reset Token and store in Cache for exactly 10 minutes
                Cache::put(
                    "password_reset_{$resetToken}",
                    [
                        'user_id' => $user->id,
                        'device_id' => resolveDeviceId($request),
                    ],
                    now()->addMinutes(10)
                );
                // Token verify hone ke baad OTP session clear kar dein
                Cache::forget("otp_session_{$request->temp_token}");

                return $this->successResponse('OTP verified successfully.', [
                    'reset_token' => $resetToken,
                    'expires_in' => 600
                ]);

            case 'profile_update':
                return $this->handleProfileUpdate($user, $request);
        }
    }

    private function findUser($input)
    {
        if (!$input)
            return null;
        $field = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $input = $field === 'phone' ? preg_replace('/[^0-9]/', '', $input) : trim($input);
        return User::where($field, $input)->first();
    }

    private function handleSuccessfulLogin($user, $request)
    {
        $userAgent = $request->userAgent();
        $language = $request->header('Accept-Language');
        $ip = $request->ip();

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $finalDeviceId = resolveDeviceId($request);



        $appVersion = $request->header('X-App-Version', '1.0.0');
        $appDeviceType = null;

        $appInfo = $request->header('app-info');

        if ($appInfo) {

            $appInfoData = json_decode($appInfo, true);

            if (
                json_last_error() === JSON_ERROR_NONE &&
                is_array($appInfoData)
            ) {
                $appInfoData = $appInfoData[0] ?? [];

                if (!empty($appInfoData['app_version'])) {
                    $appVersion = $appInfoData['app_version'];
                }

                if (isset($appInfoData['device_type'])) {
                    $appDeviceType = (string) $appInfoData['device_type'];
                }
            }
        }

        $device = $this->deviceService->processDevice(
            $user,
            $finalDeviceId,
            $appVersion,
            $ip,
            $userAgent,
            $language,
            $agent,
            $appDeviceType
        );

        $role = $request->role;

        $response = $this->authLoginService->otpLogin($user, $device, $request, $role);

        if (!$response['status']) {
            return $this->errorResponse(
                message: $response['message'],
                statusCode: $response['code'] ?? 400
            );
        }

        $userData = (new MeResource($response['user']))->resolve();

        $mergedData = array_merge($userData, [
            'token' => $response['token'],
            'refresh_token' => $response['refresh_token'],
            'expires_in' => $response['expires_in'],
            'waiter_id' => $response['waiter_id_data'],
        ]);

        return $this->successResponse(
            message: 'Successfully verified and logged in.',
            data: $mergedData
        );
    }

    private function handleProfileUpdate($user, $request)
    {
        $cacheKey = "profile_update_" . $request->temp_token;
        $updateData = Cache::get($cacheKey);

        if (!$updateData) {
            return $this->errorResponse('Update session expired. Please try again.', 400);
        }

        if ($updateData['device_id'] !== resolveDeviceId($request)) {
            return $this->forbiddenResponse('Security mismatch. Update blocked.');
        }

        $user->first_name = $updateData['first_name'];
        $user->last_name = $updateData['last_name'];
        $user->email = $updateData['email'];
        $user->phone = $updateData['phone'];
        $user->address = $updateData['address'];
        $user->username = $updateData['username'];
        $user->save();

        if (!empty($updateData['address'])) {
            \App\Models\Address::updateOrCreate(
                ['user_id' => $user->id, 'label' => \App\Enums\AddressType::HOME],
                ['address' => $updateData['address'], 'label_name' => trans('address_types.' . \App\Enums\AddressType::HOME)]
            );
        }

        Cache::forget($cacheKey);

        return $this->successResponse(
            message: 'Profile updated successfully!',
            data: new MeResource($user)
        );
    }
}