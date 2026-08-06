<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DeviceSession;
use App\Http\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    use ApiResponse;

    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email_or_phone' => 'required'
        ]);

        $user = $this->findUser($request->email_or_phone);

        if (!$user) {
            return $this->notFoundResponse('User not found.');
        }



        $tempToken = Str::uuid()->toString();

        $deviceId = resolveDeviceId($request);

        Cache::put(
            "otp_session_{$tempToken}",
            [
                'email_or_phone' => $request->email_or_phone,
                'purpose' => 'forgot_password',
                'device_id' => $deviceId,
            ],
            now()->addMinutes(10)
        );

        $result = $this->otpService->generateAndSend(
            $user,
            'forgot_password',
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
            message: 'OTP sent successfully.',
            data: [
                'temp_token' => $tempToken,
                'purpose' => 'forgot_password',
                'expires_in' => $result['expires_in'] ?? 600,
            ]
        );
    }



    public function resetPassword(Request $request)
    {
        $request->validate([
            'reset_token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $session = Cache::get("password_reset_{$request->reset_token}");

        if (!$session) {
            return $this->errorResponse('Reset session expired or invalid.', 400);
        }
        if (($session['device_id'] ?? null) !== resolveDeviceId($request)) {

            return $this->forbiddenResponse(
                'Security mismatch. Password reset blocked.'
            );
        }
        $user = User::find($session['user_id']);

        if (!$user) {
            return $this->notFoundResponse('User not found.');
        }

        if (Hash::check($request->password, $user->password)) {

            return $this->errorResponse(
                'New password cannot be the same as your current password.',
                422
            );
        }

        DB::transaction(function () use ($user, $request) {

            $user->password = Hash::make($request->password);
            $user->last_password_changed_at = now();
            $user->save();

            Cache::forget("password_reset_{$request->reset_token}");

            $this->revokeOtherSessions($user->id);
        });

        $this->sendPasswordResetNotification($user);

        return $this->updatedResponse(
            'Password updated successfully. You have been logged out of all devices.'
        );
    }


    private function findUser($input)
    {
        if (!$input)
            return null;
        $field = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $input = $field === 'phone' ? preg_replace('/[^0-9]/', '', $input) : trim($input);
        return User::where($field, $input)->first();
    }


    private function revokeOtherSessions($userId)
    {
        $sessions = DeviceSession::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->get();

        foreach ($sessions as $session) {

            $session->update([
                'revoked_at' => now()
            ]);

            Cache::forget("session_valid:{$session->id}");
        }
    }

    private function sendPasswordResetNotification($user)
    {
        try {
            // Yahan aap apna actual Mail/SMS logic daal sakte hain.
            // Example for Mail:
            /*
            Mail::raw('Your password has been successfully reset. If you did not make this change, please contact support immediately.', function ($message) use ($user) {
                $message->to($user->email)->subject('Security Alert: Password Changed');
            });
            */

            // Example for Custom Notification:
            // $user->notify(new PasswordChangedNotification());
        } catch (\Exception $e) {
            // Log error silently, notification fail hone se user block nahi hona chahiye
            \Log::error('Failed to send password reset notification: ' . $e->getMessage());
        }
    }
}