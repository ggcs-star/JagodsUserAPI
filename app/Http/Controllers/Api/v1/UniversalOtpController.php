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
            'email_or_phone' => 'required',
            'purpose' => 'required|in:login,device_verification,forgot_password'
        ]);

        $user = $this->findUser($request->email_or_phone);
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
            return $this->errorResponse($result['message'], $result['code'] ?? 400);
        }

        return $this->successResponse($result['message'], [
            'expires_in' => $result['expires_in'] ?? null
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email_or_phone' => 'required',
            'otp' => 'required|numeric',
            'purpose' => 'required|in:login,device_verification,forgot_password,profile_update',
        ]);

        $user = $this->findUser($request->email_or_phone);
        if (!$user) {
            return $this->notFoundResponse('User not found.');
        }

        $deviceId = resolveDeviceId($request);

        $verification = $this->otpService->verify(
            $user,
            $request->purpose,
            $request->otp,
            $deviceId,
            $request->ip()
        );

        if (!$verification['status']) {
            return $this->badRequestResponse($verification['message']);
        }

        switch ($request->purpose) {
            case 'login':
            case 'device_verification':
                return $this->handleSuccessfulLogin($user, $request);

            case 'forgot_password':
                return $this->successResponse('OTP verified. Please set new password.', [
                    'reset_token' => 'xyz...'
                ]);

            case 'profile_update':
                return $this->handleProfileUpdate($user, $request);
        }
    }

    private function findUser($input)
    {
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

        $device = $this->deviceService->processDevice(
            $user,
            $finalDeviceId,
            $request->header('X-App-Version', '1.0.0'),
            $ip,
            $userAgent,
            $language,
            $agent
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
        $request->validate([
            'temp_token' => 'required|string'
        ]);

        $cacheKey = "profile_update_" . $request->temp_token;
        $updateData = Cache::get($cacheKey);

        if (!$updateData) {
            return $this->badRequestResponse('Update session expired. Please try again.');
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
            data: new PrivateUserResource($user)
        );
    }
}