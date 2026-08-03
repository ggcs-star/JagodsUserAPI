<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Services\Security\ActiveSessionService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Throwable;

class ActiveSessionController extends Controller
{
    use ApiResponse;

    protected ActiveSessionService $sessionService;

    public function __construct(ActiveSessionService $sessionService)
    {
        $this->middleware('auth:api');
        $this->sessionService = $sessionService;
    }

    public function index(Request $request)
    {
        try {

            $userId = auth()->id();

            $currentDevice = $request->attributes->get('current_device');

            if (!$currentDevice) {

                return $this->unauthorizedResponse(
                    'Current device not found.'
                );
            }

            $sessions = $this->sessionService->getActiveSessions(
                $userId,
                $currentDevice->id
            );

            return $this->successResponse(
                message: 'Active sessions retrieved successfully.',
                data: $sessions
            );

        } catch (Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Internal Server Error'
            );
        }
    }
}