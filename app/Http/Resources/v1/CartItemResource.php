<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $discountPercentage = 0;

        if ($this->unit_price > 0) {
            $discountPercentage = round(
                ($this->discount_price / $this->unit_price) * 100,
                2
            );
        }

        return [
            'id' => $this->id,
            'menu_item_id' => $this->menu_item_id,

            'name' => $this->menu_name,
            'slug' => $this->menu_slug,
            'image' => $this->menu_image,

            'quantity' => (int) $this->quantity,

            // Pricing
            'unit_price' => (float) $this->unit_price,
            'discount_price' => (float) $this->discount_price,
            'final_price' => (float) $this->final_price,
            'total_price' => (float) $this->total_price,

            // Discount Info
            'has_discount' => $this->discount_price > 0,
            'discount_percentage' => $discountPercentage,

            // Availability
            'is_available' => (bool) $this->is_available,
            'is_price_changed' => (bool) $this->is_price_changed,

            // Variation
            'variation_name' => $this->variation_name,

            // Options
            'options' => $this->options ?? [],

            // Instructions
            'instructions' => $this->instructions,
        ];
    }
}