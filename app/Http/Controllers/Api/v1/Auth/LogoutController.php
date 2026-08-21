<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Services\Security\ActiveSessionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class LogoutController extends Controller
{
    use ApiResponse;

    protected ActiveSessionService $sessionService;

    public function __construct(ActiveSessionService $sessionService)
    {
        $this->middleware('auth:api');

        $this->sessionService = $sessionService;
    }

    public function action(Request $request)
    {
        try {

            $request->validate([
                'refresh_token' => 'required_without_all:device_db_id,logout_all|string',
                'device_db_id' => 'required_without_all:refresh_token,logout_all',
                'logout_all' => 'required_without_all:refresh_token,device_db_id|boolean',
            ]);

            $currentDevice = $request->attributes->get('current_device');

            if (!$currentDevice) {

                return $this->unauthorizedResponse(
                    'Current device not found.'
                );
            }

            $response = $this->sessionService->handleLogoutProcess(
                $request,
                auth('api')->id(),
                $currentDevice
            );

            if (!$response['status']) {

                return $this->errorResponse(
                    message: $response['message'],
                    statusCode: $response['code']
                );
            }

            return $this->successResponse(
                message: $response['message'],
                data: []
            );

        } catch (ValidationException $e) {

            return $this->validationResponse(
                $e->errors()
            );

        } catch (Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }

    public function deleteAccount(): JsonResponse
    {
        try {

            $user = auth('api')->user();

            if (!$user) {
                return $this->unauthorizedResponse(
                    'Unauthenticated.'
                );
            }

            if ((int) $user->status === UserStatus::INACTIVE) {

                return $this->successResponse(
                    message: 'Account is already inactive.'
                );
            }

            $user->update([
                'status' => UserStatus::INACTIVE,
            ]);

            Log::info('User account deactivated successfully.', [
                'user_id' => $user->id,
            ]);


            try {

                auth('api')->logout();

            } catch (Throwable $e) {

                Log::warning(
                    'JWT logout failed after account deactivation.',
                    [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]
                );
            }

            return $this->successResponse(
                message: 'Your account has been deactivated successfully.'
            );

        } catch (Throwable $e) {

            Log::error('Delete Account API Error.', [
                'user_id' => auth('api')->id(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Unable to deactivate account.'
            );
        }
    }
}