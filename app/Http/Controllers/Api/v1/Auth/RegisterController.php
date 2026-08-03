<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\v1\MeResource; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Http\Services\Auth\AuthRegisterService;
use App\Traits\ApiResponse;

class RegisterController extends Controller
{
    use ApiResponse;

    protected $registerService;

    public function __construct(AuthRegisterService $registerService)
    {
        $this->registerService = $registerService;
    }

    public function sendRegisterOtp(Request $request)
    {
        $validator = new RegisterRequest();
        $rules = $validator->rules();

        if (in_array($request->get('role'), [3, 4])) {
            $rules['deposit_amount'] = 'nullable|numeric';
            $rules['limit_amount'] = 'nullable|numeric';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->toArray());
        }

        $role = Role::find($request->get('role'));

        if (!$role) {
            return $this->errorResponse('Given role not found.', 401);
        }

        $deviceId = resolveDeviceId($request);

        $response = $this->registerService->processRegistrationOtp(
            $request->all(),
            $deviceId,
            $request->ip()
        );

        if (!$response['status']) {
            return $this->errorResponse($response['message'], $response['code']);
        }

        return $this->successResponse($response['message'], [
            'temp_token'  => $response['temp_token'],
            'expires_in'  => $response['expires_in'],
        ]);
    }

    public function verifyRegisterOtp(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'otp'        => 'required|numeric'
        ]);

        $deviceId = resolveDeviceId($request);

        $response = $this->registerService->verifyAndRegister(
            $request,
            $request->temp_token,
            $request->otp,
            $deviceId
        );

        if (!$response['status']) {
            return $this->errorResponse($response['message'], $response['code']);
        }

        $loginData = $response['login_data'];

        $userData = (new MeResource($response['user']))->resolve();
        
        $mergedData = array_merge($userData, [
            'token'         => $loginData['token'],
            'refresh_token' => $loginData['refresh_token'],
            'expires_in'    => $loginData['expires_in'],
            'waiter_id'     => $loginData['waiter_id_data'] ?? 0,
        ]);

        return $this->successResponse('Successfully registered and logged in.', $mergedData);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
        ]);

        $deviceId = resolveDeviceId($request);

        $response = $this->registerService->resendRegistrationOtp(
            $request->temp_token,
            $deviceId,
            $request->ip()
        );

        if (!$response['status']) {
            return $this->errorResponse($response['message'], $response['code']);
        }

        return $this->successResponse($response['message'], [
            'expires_in' => $response['expires_in']
        ]);
    }
}