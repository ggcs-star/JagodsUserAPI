<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\v1\MeResource; 
use App\Http\Services\Auth\AuthLoginService;
use App\Traits\ApiResponse;

class LoginController extends Controller
{
    use ApiResponse; 
    protected $authLoginService;

    public function __construct(AuthLoginService $authLoginService)
    {
        $this->middleware('auth:api', ['except' => ['action']]);
        $this->authLoginService = $authLoginService;
    }

  public function action(LoginRequest $request)
    {
        $login = trim($request->email);

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $credentials = ['email' => $login, 'password' => $request->password];
        } else {
            $login = preg_replace('/[^0-9]/', '', $login);
            $credentials = ['phone' => $login, 'password' => $request->password];
        }

        $response = $this->authLoginService->login($credentials, $request, $request->role);

        if (!$response['status']) {
            if (isset($response['requires_otp']) && $response['requires_otp']) {
                return $this->otpRequiredResponse(
                    message: $response['message'],
                    risk: $response['risk'] ?? null,
                    deviceId: $response['device_id'] ?? null,
                    // 👇 Data me frontend ke liye instructions bhej diye
                    data: [
                        'temp_token' => $response['temp_token'],
                        'purpose'    => $response['purpose'],
                        'expires_in' => 10,
                        'verify_instructions' => 'Pass this temp_token and otp to the /verify endpoint.'
                    ]
                );
            }

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
            message: 'Successfully Logged In',
            data: $mergedData
        );
    }
}