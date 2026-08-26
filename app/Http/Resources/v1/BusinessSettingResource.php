<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessSettingResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'free_delivery_radius' => (float) ($this['free_delivery_radius'] ?? 0),
            'charge_per_kilo' => (float) ($this['charge_per_kilo'] ?? 0),
            'basic_delivery_charge' => (float) ($this['basic_delivery_charge'] ?? 0),

            'max_delivery_radius' => (float) ($this['max_delivery_radius'] ?? 0),
            'restaurant_lat' => '23.104192',
            'restaurant_long' => '72.594234',
            'platform_fee' => (float) ($this['platform_fee'] ?? 0),
            'surge_fee' => (float) ($this['surge_fee'] ?? 0),
            'packaging_charge' => (float) ($this['packaging_charge'] ?? 0),
            'gst' => 5,
            'all_over_india_support_phone' => $this['support_phone'] ?? '',
            'your_city_support_phone' => $this['support_phone'] ?? '',
        ];
    }
}