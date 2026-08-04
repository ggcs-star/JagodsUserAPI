<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BackendController;
use App\Http\Requests\AddressRequest;
use App\Http\Resources\v1\AddressResource;
use App\Http\Services\AddressService;
use App\Models\Address;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AddressController extends BackendController
{
    use ApiResponse;

    protected AddressService $addressService;

    public function __construct(AddressService $addressService)
    {
        parent::__construct();

        $this->middleware('auth:api');

        $this->addressService = $addressService;
    }


    public function index()
    {
        try {

            $addresses = $this->addressService->allAddresses();

            return $this->successResponse(
                message: 'Address list fetched successfully.',
                data: AddressResource::collection($addresses)
            );

        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }


    public function store(AddressRequest $request)
    {
        try {

            $address = $this->addressService->store($request);

            return $this->createdResponse(
                message: 'Address added successfully.',
                data: new AddressResource($address)
            );

        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }


    public function update(AddressRequest $request, $id)
    {
        try {

            $address = Address::find($id);

            if (!$address) {
                return $this->notFoundResponse('Address not found.');
            }

            $address = $this->addressService->update($address, $request);

            return $this->updatedResponse(
                message: 'Address updated successfully.',
                data: new AddressResource($address)
            );

        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }


   public function destroy($id)
{
    try {

        $address = Address::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$address) {
            return $this->notFoundResponse(
                'Address not found.'
            );
        }

        $address->delete();

        return $this->deletedResponse(
            message: 'Address deleted successfully.'
        );

    } catch (\Throwable $e) {

        return $this->serverErrorResponse(
            message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
        );
    }
}
}