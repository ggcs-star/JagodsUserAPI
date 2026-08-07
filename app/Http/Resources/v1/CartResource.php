<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\Module;
use App\Enums\OrderTypeStatus;
class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $rawFees = [
            'packing_charge' => (float) $this->packing_charge,
            'platform_fee' => (float) $this->platform_fee,
            'surge_fee' => (float) $this->surge_fee,
            'large_order_fee' => (float) $this->large_order_fee,
            'tip_amount' => (float) $this->tip_amount,
        ];


        $formattedFees = [];
        foreach ($rawFees as $key => $amount) {
            if ($amount > 0) {
                $formattedFees[] = [
                    'key' => $key,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'amount' => $amount
                ];
            }
        }
        $firstItem = $this->relationLoaded('items')
            ? $this->items->first()
            : null;

        $moduleSlug = optional(
            optional($firstItem)->menuItem?->module
        )->slug;

        $pickupAvailable = $moduleSlug !== Module::ALL_OVER_INDIA_SLUG;

        $allowedOrderTypes = $pickupAvailable
            ? [
                [
                    'id' => OrderTypeStatus::DELIVERY,
                    'name' => 'Delivery',
                ],
                [
                    'id' => OrderTypeStatus::PICKUP,
                    'name' => 'Pickup',
                ],
            ]
            : [
                [
                    'id' => OrderTypeStatus::DELIVERY,
                    'name' => 'Delivery',
                ],
            ];
        return [
            'cart_id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'address_id' => $this->address_id,
            'order_type' => (int) $this->order_type,
            'order_instructions' => $this->order_instructions,
            'pickup_available' => $pickupAvailable,
            'allowed_order_types' => $allowedOrderTypes,
            'bill_details' => [

                'mrp_total' => (float) ($this->subtotal + $this->product_discount),

                'product_discount' => (float) $this->product_discount,

                'subtotal' => (float) $this->subtotal,

                'coupon_discount' => (float) $this->discount,

                'gst_amount' => (float) $this->gst_amount,

                'delivery_charge' => (float) $this->delivery_charge,

                'fees' => $formattedFees,

                'total_payable' => (float) $this->total,
            ],

            'sync_messages' => $this->sync_messages ?? [],

            'applied_coupon' => $this->whenLoaded('coupon', function () {
                return [
                    'code' => $this->coupon->slug,
                    'discount_type' => $this->coupon->discount_type,
                    'amount' => (float) $this->coupon->amount,
                ];
            }),

            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}