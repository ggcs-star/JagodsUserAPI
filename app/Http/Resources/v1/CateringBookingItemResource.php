<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringBookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,

            'catering_booking_section_id' =>
                (int) $this->catering_booking_section_id,

            'catering_package_item_id' =>
                $this->catering_package_item_id
                    ? (int) $this->catering_package_item_id
                    : null,

            'menu_item_id' =>
                $this->menu_item_id
                    ? (int) $this->menu_item_id
                    : null,

            'menu_item_name' =>
                $this->menu_item_name,

            'menu_item_description' =>
                $this->menu_item_description,

            'unit_price' =>
                (float) $this->unit_price,

            'discount_price' =>
                (float) $this->discount_price,

            'extra_price' =>
                (float) $this->extra_price,

            'final_unit_price' =>
                (float) $this->final_unit_price,

            'is_default' =>
                (bool) $this->is_default,

            'is_selected' =>
                (bool) $this->is_selected,

            'quantity' =>
                (int) $this->quantity,

            'item_total' =>
                (float) $this->item_total,

            'sort_order' =>
                (int) $this->sort_order,
        ];
    }
}