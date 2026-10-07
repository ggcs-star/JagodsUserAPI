<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BackendController;
use App\Http\Requests\Api\StoreCateringBookingRequest;
use App\Http\Resources\v1\CateringBookingResource;
use App\Http\Services\CateringBookingService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CateringBookingController extends BackendController
{
    use ApiResponse;

    public function __construct(
        protected CateringBookingService $cateringBookingService
    ) {
        parent::__construct();

        $this->middleware('auth:api');
    }

    /**
     * Create Catering Booking
     */
    public function store(StoreCateringBookingRequest $request)
    {
        try {

            $booking = $this->cateringBookingService->createBooking(
                $request->validated(),
                auth()->user()
            );

            return $this->createdResponse(
                message: 'Catering booking submitted successfully.',
                data: new CateringBookingResource($booking)
            );

        } catch (ValidationException $e) {

            return $this->validationResponse(
                $e->errors()
            );

        } catch (Throwable $e) {

            Log::error(
                'Catering booking API failed.',
                [
                    'user_id' =>
                        auth()->id(),

                    'request' =>
                        $request->except([
                            'password',
                            'token',
                        ]),

                    'exception' =>
                        $e->getMessage(),
                ]
            );

            return $this->serverErrorResponse(
                message: 'Unable to submit catering booking at the moment.'
            );
        }
    }

    /**
     * Get My Catering Bookings
     */
    public function index(Request $request)
    {
        try {

            $validated = $request->validate([
                'page' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'per_page' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],
            ]);

            $bookings =
                $this->cateringBookingService->getMyBookings(
                    auth()->user(),
                    (int) (
                        $validated['per_page'] ?? 20
                    )
                );

            return $this->successPaginationResponse(
                message: 'Catering bookings retrieved successfully.',

                paginator: $bookings,

                data: CateringBookingResource::collection(
                    $bookings->getCollection()
                )
            );

        } catch (Throwable $e) {

            Log::error(
                'Catering booking list API failed.',
                [
                    'user_id' =>
                        auth()->id(),

                    'exception' =>
                        $e->getMessage(),
                ]
            );

            return $this->serverErrorResponse(
                message: 'Unable to retrieve catering bookings at the moment.'
            );
        }
    }

    /**
     * Get Catering Booking Details
     */
    public function show(int $id)
    {
        try {

            $booking =
                $this->cateringBookingService->getBookingDetails(
                    $id,
                    auth()->user()
                );

            return $this->successResponse(
                message: 'Catering booking details retrieved successfully.',
                data: new CateringBookingResource($booking)
            );

        } catch (ModelNotFoundException $e) {

            return $this->notFoundResponse(
                message: 'Catering booking not found.'
            );

        } catch (Throwable $e) {

            Log::error(
                'Catering booking details API failed.',
                [
                    'user_id' =>
                        auth()->id(),

                    'booking_id' =>
                        $id,

                    'exception' =>
                        $e->getMessage(),
                ]
            );

            return $this->serverErrorResponse(
                message: 'Unable to retrieve catering booking details at the moment.'
            );
        }
    }
}