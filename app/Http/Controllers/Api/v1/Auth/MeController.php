<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Models\User;
use App\Models\Report;
use App\Models\Address;
use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\RatingStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\RestaurantRating;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use Illuminate\Support\Facades\File;
use App\Http\Resources\v1\MeResource;
use App\Http\Services\ComplaintService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Api\ProfileUpdateRequest;
use App\Http\Requests\Api\PasswordUpdateRequest;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Http\Services\OtpService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class MeController extends Controller
{
    use ApiResponse;
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->middleware('auth:api');
        $this->otpService = $otpService;
    }

    public function action(Request $request)
    {
        try {
            $data = new MeResource($request->user());

            return $this->successResponse(
                message: 'Profile fetched successfully.',
                data: $data
            );

        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }

    public function refresh()
    {
        $token = JWTAuth::getToken();
        if (!$token) {
            return $this->unauthorizedResponse('Token not provided.');
        }

        try {
            $token = JWTAuth::refresh($token);

            return $this->successResponse(
                message: 'Token refreshed successfully.',
                data: [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 3600000000000,
                ]
            );
        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse($e->getMessage());
        } catch (\Throwable $e) {
            return $this->serverErrorResponse(
                config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            );
        }
    }

    public function update(Request $request)
    {
        try {

            $profile = auth()->user();

            if (blank($profile)) {
                return $this->unauthorizedResponse('Unauthorized access.');
            }

            $validator = Validator::make(
                $request->all(),
                (new ProfileUpdateRequest($profile->id))->rules()
            );

            if ($validator->fails()) {
                return $this->validationResponse($validator->errors()->toArray());
            }

            $firstName = '';
            $lastName = '';

            if ($request->filled('name')) {
                [$firstName, $lastName] = $this->splitName($request->name);
            }

            $newEmail = $request->email;
            $newPhone = $request->phone;

            $isSensitiveChange =
                ($newEmail !== $profile->email) ||
                ($newPhone !== $profile->phone);

            if ($isSensitiveChange) {

                $tempToken = Str::uuid()->toString();

                $updateData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $newEmail,
                    'phone' => $newPhone,
                    'address' => $request->address,
                    'username' => $request->username ?? $profile->username,
                    'device_id' => resolveDeviceId($request),
                ];

                Cache::put(
                    "profile_update_{$tempToken}",
                    $updateData,
                    now()->addMinutes(10)
                );

                // Resend OTP ke liye bhi session save karo
                Cache::put(
                    "otp_session_{$tempToken}",
                    [
                        'email_or_phone' => $newEmail ?: $newPhone,
                        'purpose' => 'profile_update',
                    ],
                    now()->addMinutes(10)
                );

                $tempUser = clone $profile;
                $tempUser->email = $newEmail;
                $tempUser->phone = $newPhone;

                $result = $this->otpService->generateAndSend(
                    $tempUser,
                    'profile_update',
                    resolveDeviceId($request),
                    $request->ip()
                );

                if (!$result['status']) {
                    return $this->errorResponse(
                        message: $result['message'] ?? 'Failed to send OTP.',
                        statusCode: $result['code'] ?? 400
                    );
                }

                return $this->otpRequiredResponse(
                    message: 'OTP sent to your new contact details. Please verify to confirm changes.',
                    data: [
                        'temp_token' => $tempToken,
                        'purpose' => 'profile_update',
                        'expires_in' => $result['expires_in'] ?? 600,
                    ]
                );
            }

            return $this->performDirectUpdate(
                $profile,
                $request,
                $firstName,
                $lastName
            );

        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }


    private function performDirectUpdate($profile, $request, $firstName, $lastName)
    {
        $profile->first_name = $firstName;
        $profile->last_name = $lastName;
        $profile->address = $request->get('address');

        if ($request->username) {
            $profile->username = $request->username;
        }
        $profile->save();

        if ($profile->address != $request->get('address') && !empty($request->get('address'))) {
            Address::create([
                'label' => AddressType::HOME,
                'address' => $request->get('address'),
                'label_name' => trans('address_types.' . AddressType::HOME),
                'user_id' => $profile->id,
            ]);
        }

        if ($request->file('image')) {
            $profile->media()->delete();
            $profile->addMedia($request->file('image'))->toMediaCollection('user');
        }

        return $this->updatedResponse('Successfully Updated Profile');
    }

    private function splitName($name)
    {
        $name = trim($name);
        $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim(preg_replace('#' . $last_name . '#', '', $name));
        return [$first_name, $last_name];
    }

    public function changePassword(Request $request)
    {
        $validator = new PasswordUpdateRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->toArray());
        }

        $profile = auth()->user();
        $profile->password = bcrypt($request->get('password'));
        $profile->save();

        return $this->updatedResponse('Successfully Updated Password');
    }

    public function device(Request $request)
    {
        $validator = Validator::make($request->all(), ['device_token' => 'required']);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->toArray());
        }

        $user = auth()->user();
        $user->device_token = $request->device_token;
        $user->save();

        return $this->updatedResponse('Successfully device updated.');
    }

    public function review($id)
    {
        $ratingReview = RestaurantRating::where(['user_id' => auth()->user()->id, 'restaurant_id' => $id])->first();

        if (blank($ratingReview)) {
            return $this->notFoundResponse('Review not found.');
        }

        return $this->successResponse(
            message: 'Review fetched successfully.',
            data: $ratingReview
        );
    }

    public function saveReview(Request $request)
    {
        $request->merge([
            'user_id' => auth()->id()
        ]);

        $validator = Validator::make($request->all(), $this->reviewValidateArray());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->toArray());
        }

        RestaurantRating::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'restaurant_id' => $request->restaurant_id
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
                'status' => RatingStatus::ACTIVE,
            ]
        );

        return $this->successResponse(
            message: 'Your rating successfully saved.'
        );
    }

    public function reviewValidateArray()
    {
        return [
            'rating' => 'required|numeric|min:1|max:5',
            'review' => 'required|string|max:500',
            'user_id' => 'required|numeric',
            'restaurant_id' => 'required|numeric',
        ];
    }

    public function reportCheck($id)
    {
        $report = Report::where('order_id', $id)->first();

        if (blank($report)) {
            return $this->successResponse(
                message: 'No reports yet.',
                data: ['isNew' => true]
            );
        } else {
            return $this->successResponse(
                message: trans('report_statues_frontend.' . $report->status),
                data: ['isNew' => false]
            );
        }
    }

    public function storeReport(ReportRequest $request)
    {
        $report = app(ComplaintService::class)->storeReport($request);

        if ($report) {
            return $this->successResponse(
                message: 'Reported successfully.'
            );
        } else {
            return $this->errorResponse(
                message: "Something's Wrong!",
                statusCode: 502
            );
        }
    }
}