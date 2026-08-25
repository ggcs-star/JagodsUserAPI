<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceAppInfoService
{
    public function parse(Request $request): array
    {
        $appVersion = $request->header(
            'X-App-Version',
            '1.0.0'
        );

        $deviceType = null;

        $appInfo = $request->header('app-info');

        if (blank($appInfo)) {
            return [
                'app_version' => $appVersion,
                'device_type' => $deviceType,
            ];
        }

        $appInfoData = json_decode($appInfo, true);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !is_array($appInfoData)
        ) {
            return [
                'app_version' => $appVersion,
                'device_type' => $deviceType,
            ];
        }

        if (isset($appInfoData['device_type'])) {

            $deviceType = (string) $appInfoData['device_type'];

            if (!empty($appInfoData['app_version'])) {
                $appVersion = (string) $appInfoData['app_version'];
            }

        } else {

            $appInfoData = $appInfoData[0] ?? [];

            if (is_array($appInfoData)) {

                if (!empty($appInfoData['app_version'])) {
                    $appVersion = (string) $appInfoData['app_version'];
                }

                if (isset($appInfoData['device_type'])) {
                    $deviceType = (string) $appInfoData['device_type'];
                }
            }
        }

        return [
            'app_version' => $appVersion,
            'device_type' => $deviceType,
        ];
    }

    public function updateFcmToken(
        User $user,
        ?string $fcmToken,
        ?string $deviceType
    ): void {
        if (blank($fcmToken) || blank($deviceType)) {
            return;
        }

        if (in_array($deviceType, ['0', '1'], true)) {

            $user->update([
                'device_token' => $fcmToken,
            ]);

            Log::info('FCM device token updated.', [
                'user_id' => $user->id,
                'device_type' => $deviceType,
            ]);

            return;
        }

        if ($deviceType === '3') {

            $user->update([
                'web_token' => $fcmToken,
            ]);

            Log::info('FCM web token updated.', [
                'user_id' => $user->id,
                'device_type' => $deviceType,
            ]);

            return;
        }

        Log::warning('Unknown device type for FCM token.', [
            'user_id' => $user->id,
            'device_type' => $deviceType,
        ]);
    }
}