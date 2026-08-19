<?php

namespace App\Http\Requests;

use App\Enums\AddressType;
use App\Models\Address;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AddressRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [

            'label' => [
                'required',
                'numeric',
                Rule::in([
                    AddressType::HOME,
                    AddressType::WORK,
                    AddressType::OTHER,
                ]),
            ],

            'label_name' => [
                'nullable',
                'string',
                'max:50',
            ],

            'receiver_name' => [
                'required',
                'string',
                'max:100',
            ],

            'receiver_phone' => [
                'required',
                'numeric',
                'digits:10',
            ],


            'new_address' => [
                'required',
                'string',
                'max:200',
            ],

            'apartment' => [
                'nullable',
                'string',
                'max:200',
            ],

            'landmark' => [
                'nullable',
                'string',
                'max:150',
            ],


            'city' => [
                'required',
                'string',
                'max:100',
            ],

            'state' => [
                'required',
                'string',
                'max:100',
            ],

            'country' => [
                'required',
                'string',
                'max:100',
            ],

            'pincode' => [
                'required',
                'numeric',
                'digits:6',
            ],

            'lat' => [
                'required',
                'numeric',
                'between:-90,90',
            ],

            'long' => [
                'required',
                'numeric',
                'between:-180,180',
            ],


            'is_default' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {


            if (
                $this->label == AddressType::OTHER &&
                blank($this->label_name)
            ) {
                $validator->errors()->add(
                    'label_name',
                    'This field is required when label is Other.'
                );
            }

            if ($this->uniqueOtherLabel()) {
                $validator->errors()->add(
                    'label_name',
                    'This Label is already created!'
                );
            }
        });
    }

    private function uniqueOtherLabel(): bool
    {
        if ($this->label != AddressType::OTHER) {
            return false;
        }

        $address = Address::where('user_id', auth()->id())
            ->where('label', AddressType::OTHER)
            ->where('label_name', $this->label_name)
            ->when(
                $this->id,
                fn($query) => $query->where('id', '!=', $this->id)
            )
            ->first();

        return $address !== null;
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