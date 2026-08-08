<?php

namespace App\Http\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\DeviceSession;
use App\Enums\UserStatus;
use App\Enums\UserRole;
use App\Http\Resources\v1\RestaurantResource;
use App\Http\Services\Security\SecurityRiskService;
use App\Http\Services\DeviceIdentificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Agent;
use Exception;
use App\Http\Services\OtpService;

class AuthLoginService
{

    protected $deviceService;
    protected $riskService;
    protected $otpService;

    public function __construct(
        DeviceIdentificationService $deviceService,
        SecurityRiskService $riskService,
        OtpService $otpService
    ) {
        $this->deviceService = $deviceService;
        $this->riskService = $riskService;
        $this->otpService = $otpService;
    }

    public function login(array $credentials, $request, $requestedRole = null): array
    {
        if (!auth('api')->validate($credentials)) {
            return [
                'status' => false,
                'code' => 401,
                'message' => 'Invalid credentials.'
            ];
        }

        $userField = isset($credentials['email']) ? 'email' : 'phone';

        $user = User::where(
            $userField,
            $credentials[$userField]
        )->first();

        if (!$user) {
            return [
                'status' => false,
                'code' => 404,
                'message' => 'User not found.'
            ];
        }

        if ($user->status == UserStatus::INACTIVE) {
            return [
                'status' => false,
                'code' => 403,
                'message' => 'Your account is inactive.'
            ];
        }

        if ($requestedRole && ($requestedRole != $user->myrole)) {
            return [
                'status' => false,
                'code' => 403,
                'message' => "You don't have permission to login to this portal."
            ];
        }

        $userAgent = $request->userAgent();
        $language = $request->header('Accept-Language');
        $ip = $request->ip();

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $deviceId = resolveDeviceId($request);

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
            $deviceId,
            $appVersion,
            $ip,
            $userAgent,
            $language,
            $agent,
            $appDeviceType
        );

        $riskAnalysis = $this->riskService->analyzeRisk($device, $user, $ip);

        if ($riskAnalysis['action'] === 'BLOCK') {
            $device->update([
                'trust_level' => 'BLOCKED',
                'blocked_at' => now(),
            ]);

            return [
                'status' => false,
                'code' => 403,
                'message' => 'Access blocked due to high security risk.',
                'risk' => $riskAnalysis
            ];
        }

        // ... (Pichla code same rahega)
        if ($riskAnalysis['action'] === 'REQUIRE_OTP') {
            $otpResult = $this->otpService->generateAndSend(
                $user,
                'device_verification',
                $deviceId,
                $ip
            );

            if (!$otpResult['status']) {
                return [
                    'status' => false,
                    'code' => $otpResult['code'] ?? 400,
                    'message' => $otpResult['message']
                ];
            }

            // 👇 Temp token generate kiya aur details cache kar li
            $tempToken = Str::uuid()->toString();
            Cache::put("otp_session_{$tempToken}", [
                'email_or_phone' => $credentials[$userField],
                'purpose' => 'device_verification'
            ], now()->addMinutes(10));

            return [
                'status' => false,
                'code' => 401,
                'message' => 'Suspicious login detected. OTP sent to your registered contact.',
                'requires_otp' => true,
                'risk' => $riskAnalysis,
                'device_id' => $device->id,
                'temp_token' => $tempToken,              // 👇 Token response me add kiya
                'purpose' => 'device_verification'
            ];
        }
        // ...

        return $this->generateTokensAndSession($user, $device, $request, $requestedRole);
    }

    public function otpLogin(User $user, $device, $request, $requestedRole = null): array
    {
        if ($requestedRole && ($requestedRole != $user->myrole)) {
            return ['status' => false, 'code' => 403, 'message' => "Permission denied."];
        }

        if ($device) {
            $device->update([
                'trust_level' => 'VERIFIED',
                'failed_attempts' => 0
            ]);
        }

        return $this->generateTokensAndSession($user, $device, $request, $requestedRole);
    }

    private function generateTokensAndSession($user, $device, $request, $role): array
    {
        $refreshToken = Str::random(64);
        $session = null;

        try {
            DB::beginTransaction();

            if ($device) {
                $device->increment('login_count');
                $device->update([
                    'last_login_at' => now(),
                    'last_active_at' => now(),
                    'is_current' => true,
                ]);

                DeviceSession::where('user_device_id', $device->id)
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);

                $session = DeviceSession::create([
                    'user_id' => $user->id,
                    'user_device_id' => $device->id,
                    'refresh_token' => hash('sha256', $refreshToken),
                    'ip_address' => $request->ip(),
                    'expires_at' => now()->addDays(90),
                ]);

                Cache::put("session_valid:{$session->id}", $user->id, now()->addDays(90));
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            return ['status' => false, 'code' => 500, 'message' => 'Internal server error while creating session.'];
        }

        $customClaims = $session ? ['sid' => $session->id] : [];
        $token = auth('api')->claims($customClaims)->login($user);

        $waiterId = 0;

        if ($role == UserRole::WAITER && $user->waiter) {
            $waiterId = $user->waiter->id;
        }

        return [
            'status' => true,
            'code' => 200,
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
            'device' => $device,
            'waiter_id_data' => $waiterId,
        ];
    }
}