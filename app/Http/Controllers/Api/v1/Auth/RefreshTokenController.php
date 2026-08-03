<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Traits\ApiResponse;

class RefreshTokenController extends Controller
{
    use ApiResponse;

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        $hashedInputToken = hash('sha256', $request->refresh_token);

        $session = DeviceSession::with('user', 'device')
            ->where('refresh_token', $hashedInputToken)
            ->first();

        if (!$session) {
            // Updated to errorResponse
            return $this->errorResponse('Invalid refresh token. Please login again.', 401);
        }

        if ($session->revoked_at !== null) {
            $activeSessions = DeviceSession::where('user_device_id', $session->user_device_id)
                ->whereNull('revoked_at')
                ->get();

            foreach ($activeSessions as $activeSession) {
                Cache::forget("session_valid:{$activeSession->id}");
                $activeSession->update(['revoked_at' => now()]);
            }

            // Updated to errorResponse
            return $this->errorResponse('Security alert: Token reuse detected. All sessions revoked. Please login again.', 401);
        }

        $currentDeviceId = resolveDeviceId($request);
        
        if ($currentDeviceId && $session->device && $session->device->device_id !== $currentDeviceId) {
            $session->update(['revoked_at' => now()]);
            Cache::forget("session_valid:{$session->id}");

            // Updated to errorResponse
            return $this->errorResponse('Device verification failed. Token revoked.', 403);
        }

        if ($session->expires_at < now()) {
            $session->update(['revoked_at' => now()]);
            Cache::forget("session_valid:{$session->id}");

            // Updated to errorResponse
            return $this->errorResponse('Session expired. Please login again.', 401);
        }

        if ($session->device && $session->device->trust_level === 'BLOCKED') {
            $session->update(['revoked_at' => now()]);
            Cache::forget("session_valid:{$session->id}");

            // Updated to errorResponse
            return $this->errorResponse('This device has been blocked.', 403);
        }

        $newRawRefreshToken = Str::random(64);
        $newSession = null;

        try {
            DB::transaction(function () use ($session, $newRawRefreshToken, $request, &$newSession) {
                $session->update([
                    'revoked_at' => now(),
                    'last_used_at' => now(),
                ]);
                Cache::forget("session_valid:{$session->id}");

                $newSession = DeviceSession::create([
                    'user_id' => $session->user_id,
                    'user_device_id' => $session->user_device_id,
                    'refresh_token' => hash('sha256', $newRawRefreshToken),
                    'ip_address' => $request->ip(),
                    'expires_at' => now()->addDays(90),
                ]);
            });
        } catch (\Exception $e) {
            // Updated to errorResponse
            return $this->errorResponse('Internal server error while refreshing token.', 500);
        }

        Cache::put("session_valid:{$newSession->id}", $newSession->user_id, now()->addDays(90));

        $customClaims = ['sid' => $newSession->id];
        $newAccessToken = auth('api')->claims($customClaims)->login($session->user);

        $data = [
            'token' => $newAccessToken,
            'refresh_token' => $newRawRefreshToken,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];

        // Updated successResponse parameters (message first, then data)
        return $this->successResponse('Tokens refreshed successfully.', $data);
    }
}