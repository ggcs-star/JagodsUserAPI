<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,

            'user_id' =>
            $this->user_id
                ? (int) $this->user_id
                : null,

            'restaurant_id' =>
            (int) $this->restaurant_id,

            'module_id' =>
            $this->module_id
                ? (int) $this->module_id
                : null,

            'catering_package_id' =>
            $this->catering_package_id
                ? (int) $this->catering_package_id
                : null,

            'address_id' =>
            $this->address_id
                ? (int) $this->address_id
                : null,

            'package_name' =>
            $this->package_name,

            'package_price' =>
            (float) $this->package_price,

            'package_price_type' =>
            $this->package_price_type,

            'guest_count' =>
            (int) $this->guest_count,

            'event_date' =>
            $this->event_date?->format('Y-m-d'),

            'event_time' =>
            $this->event_time,

            'event_type' =>
            $this->event_type,

            'customer_name' =>
            $this->customer_name,

            'customer_mobile' =>
            $this->customer_mobile,

            'customer_email' =>
            $this->customer_email,

            'event_address' =>
            $this->event_address,

            'event_lat' =>
            $this->event_lat !== null
                ? (float) $this->event_lat
                : null,

            'event_long' =>
            $this->event_long !== null
                ? (float) $this->event_long
                : null,

            'special_instructions' =>
            $this->special_instructions,
            'sub_total' =>
            (float) $this->sub_total,

            'total_amount' =>
            (float) $this->total_amount,

            'status' =>
            (int) $this->status,

            'created_at' =>
            $this->created_at?->toISOString(),

            'updated_at' =>
            $this->updated_at?->toISOString(),

            'cover_image' => $this->whenLoaded('package', function () {
                return $this->package->getMedia('catering_package_images')
                    ->filter(function ($media) {
                        return (bool) $media->getCustomProperty('is_cover', false);
                    })
                    ->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => $media->getUrl(),
                        ];
                    })
                    ->values();
            }),

            'images' => $this->whenLoaded('package', function () {
                return $this->package->getMedia('catering_package_images')
                    ->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => $media->getUrl(),
                            'is_cover' => (bool) $media->getCustomProperty('is_cover', false),
                        ];
                    })
                    ->values();
            }),

             'sections' =>
            CateringBookingSectionResource::collection(
                $this->whenLoaded('sections')
            ),
        ];
    }
}
