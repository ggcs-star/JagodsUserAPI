<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Coupon
            |--------------------------------------------------------------------------
            */
            'coupon' => [
                'required',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Restaurant
            |--------------------------------------------------------------------------
            | One selected restaurant can be sent.
            |--------------------------------------------------------------------------
            */
            'restaurant_id' => [
                'required',
                'integer',
    
            ],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            | Multiple items allowed.
            |--------------------------------------------------------------------------
            */
            'items' => [
                'required',
                'array',
                'min:1',
            ],

            /*
            |--------------------------------------------------------------------------
            | Menu Item
            |--------------------------------------------------------------------------
            */
            'items.*.menu_item_id' => [
                'required',
                'integer',
                'exists:menu_items,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Variation
            |--------------------------------------------------------------------------
            */
            'items.*.variation_id' => [
                'nullable',
                'integer',
                'exists:menu_item_variations,id',
            ],

           
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

         
            'items.*.options' => [
                'nullable',
                'array',
            ],

            'items.*.options.*' => [
                'integer',
                'exists:menu_item_options,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'coupon.required' =>
                'Coupon code is required.',

            'restaurant_id.required' =>
                'Restaurant is required.',

            'restaurant_id.exists' =>
                'Restaurant not found.',

            'items.required' =>
                'Cart items are required.',

            'items.array' =>
                'Items must be an array.',

            'items.min' =>
                'At least one item is required.',

            'items.*.menu_item_id.required' =>
                'Menu item is required.',

            'items.*.menu_item_id.exists' =>
                'Menu item not found.',

            'items.*.variation_id.exists' =>
                'Variation not found.',

            'items.*.quantity.required' =>
                'Quantity is required.',

            'items.*.quantity.min' =>
                'Quantity must be at least 1.',

            'items.*.options.array' =>
                'Options must be an array.',

            'items.*.options.*.exists' =>
                'Selected option not found.',
        ];
    }
}