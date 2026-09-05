<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemVariationResource extends JsonResource
{
    public function toArray($request)
    {
        $basePrice = (float) ($this->menuItem?->unit_price ?? 0);
        $additionalPrice = (float) ($this->price ?? 0);
        return [
            'id' => (int) $this->id,

            'menu_item_id' => (int) $this->menu_item_id,

            'variation_group_id' => $this->variation_group_id
                ? (int) $this->variation_group_id
                : null,

            'name' => $this->name,

            'price' => $additionalPrice,

            'final_price' => $basePrice + $additionalPrice,
            'discount_price' => (float) (
                $this->discount_price ?? 0
            ),

            'sort_order' => (int) (
                $this->sort_order ?? 0
            ),

            'status' => (int) (
                $this->status ?? 1
            ),
        ];
    }
}
