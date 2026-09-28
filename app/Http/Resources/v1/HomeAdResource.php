<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class HomeAdResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this['id'] ?? null,
            'title' => $this['title'] ?? null,
            'subtitle' => $this['subtitle'] ?? null,
            'media_type' => $this['media_type'] ?? null,

            'cta' => $this['cta'] ?? [],

            'offers' => $this['offers'] ?? [],

            'media_url' => $this['media_url'] ?? null,

            'redirect_url' => $this['redirect_url'] ?? null,

            'theme_color' => $this['theme_color'] ?? null,

            'placement' => $this['placement'] ?? null,

            'priority' => $this['priority'] ?? null,
        ];
    }
}