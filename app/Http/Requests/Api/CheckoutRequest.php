<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PaymentMethod;
use App\Enums\Module;
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
             'module_id' => [
                'required',
                'integer',
                'in:' . Module::YOUR_CITY . ',' . Module::ALL_OVER_INDIA,
            ],
            'restaurant_id' => [
    'required',
    'integer',
    'min:0',
],
            'order_type' => 'required|integer',
            'address_id' => 'nullable|integer|exists:addresses,id',
            'order_instructions' => 'nullable|string|max:500',
            'tip_amount' => 'nullable|numeric|min:0',
            'coupon_code' => 'nullable|string|max:100',
            'payment_method' => 'required|integer|in:' . PaymentMethod::CASH_ON_DELIVERY . ',' . PaymentMethod::RAZORPAY,
            'delivery_charge' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.variation_id' => 'nullable|integer|exists:menu_item_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_price' => 'required|numeric|min:0',
            'items.*.final_price' => 'required|numeric|min:0',
            
            'items.*.options' => 'nullable|array',
            'items.*.options.*.id' => 'required|integer|exists:menu_item_options,id',
            'items.*.options.*.price' => 'required|numeric|min:0',
            
            'items.*.instructions' => 'nullable|string|max:500',
        ];
    }
}