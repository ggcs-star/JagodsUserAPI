<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringBookingSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,

            'catering_booking_id' =>
                (int) $this->catering_booking_id,

            'catering_package_section_id' =>
                $this->catering_package_section_id
                    ? (int) $this->catering_package_section_id
                    : null,

            'name' =>
                $this->name,

            'description' =>
                $this->description ?? '',

            'selection_type' =>
                $this->selection_type,

            'min_selections' =>
                (int) $this->min_selections,

            'max_selections' =>
                (int) $this->max_selections,

            'sort_order' =>
                (int) $this->sort_order,

            'items' =>
                CateringBookingItemResource::collection(
                    $this->whenLoaded('items')
                ),
        ];
    }
}