<?php

namespace App\Http\Requests;

use App\Enums\AddressType;
use App\Models\Address;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddressRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'label' => ['required', 'numeric'],
            'label_name' => ['nullable', 'string'],

            'lat' => ['required'],
            'long' => ['required'],

            'new_address' => ['required', 'max:200'],
            'apartment' => ['nullable', 'max:200'],

            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'numeric', 'digits:10'],

            'pincode' => ['required', 'numeric', 'digits:6'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if ($this->uniqueOtherLabel()) {
                $validator->errors()->add(
                    'label_name',
                    'This Label is already created!'
                );
            }

            if (
                $this->label == AddressType::OTHER &&
                blank($this->label_name)
            ) {
                $validator->errors()->add(
                    'label_name',
                    'This field is required!'
                );
            }
        });
    }

    private function uniqueOtherLabel()
    {
        if ($this->label == AddressType::OTHER) {

            $address = Address::where([
                'user_id' => auth()->id(),
                'label_name' => $this->label_name,
            ])->first();

            if ($address && $address->id != $this->id) {
                return true;
            }
        }

        return false;
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'success' => false,
                'status_code' => 422,
                'errors' => $validator->errors()->toArray(),
                'message' => 'Validation Error',
                'data' => [],
            ], 422)
        );
    }
}