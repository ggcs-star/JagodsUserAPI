<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VersionController extends Controller
{
    use ApiResponse;

    public function versionShow(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'device_version' => [
                    'required',
                    'string',
                    'max:20',
                ],

                'device_type' => [
                    'required',
                    'in:0,1',
                ],
            ]);

            if ($validator->fails()) {
                return $this->validationResponse(
                    $validator->errors()->toArray()
                );
            }

            $deviceVersion = trim(
                $request->input('device_version')
            );

            $deviceType = (int) $request->input(
                'device_type'
            );
         
            $appVersion = AppVersion::query()
                ->where('device_type', $deviceType)
                ->where('is_active', true)
                ->orderByDesc('released_at')
                ->first();

            if (!$appVersion) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'status_code' => 404,
                    'errors' => [],
                    'message' => 'App version configuration not found.',
                    'data' => [],
                ], 404);
            }

      

            if (
                version_compare(
                    $deviceVersion,
                    $appVersion->version,
                    '>='
                )
            ) {

                $status = 1;

            } elseif (
                version_compare(
                    $deviceVersion,
                    $appVersion->minimum_supported_version,
                    '>='
                )
            ) {

                $status = 2;

            } else {

                $status = 0;
            }

            return response()->json([
                'status' => true,
                'success' => true,
                'status_code' => 200,
                'errors' => [],
                'message' => 'Version retrieved',

                'data' => [
                    'device_version' => $appVersion->version,

                    'device_type' => $appVersion->platform,

                    'status' => $status,

                    'whats_new' => $appVersion->whats_new,

                    'app_storage_url' => $appVersion->app_storage_url,
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'success' => false,
                'status_code' => 500,
                'errors' => [],
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Unable to retrieve app version.',
                'data' => [],
            ], 500);
        }
    }
}