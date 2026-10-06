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

            'card' => [
                'background_image' => $this['card']['background_image'] ?? null,

                'icon' => $this['card']['icon'] ?? null,

                'title' => $this['card']['title'] ?? null,

                'button' => [
                    'text' => $this['card']['button']['text'] ?? null,
                    'link' => $this['card']['button']['link'] ?? null,
                ],

                'know_more' => [
                    'text' => $this['card']['know_more']['text'] ?? null,
                ],

                'details' => [
                    'background_image' =>
                        $this['card']['details']['background_image'] ?? null,

                    'icon' =>
                        $this['card']['details']['icon'] ?? null,

                    'title' =>
                        $this['card']['details']['title'] ?? null,

                    'subtitle' =>
                        $this['card']['details']['subtitle'] ?? null,

                    'button' => [
                        'text' =>
                            $this['card']['details']['button']['text'] ?? null,

                        'link' =>
                            $this['card']['details']['button']['link'] ?? null,
                    ],

                    'offers' => collect(
                        $this['card']['details']['offers'] ?? []
                    )->map(function ($offer) {
                        return [
                            'icon' => $offer['icon'] ?? null,
                            'title' => $offer['title'] ?? null,
                            'subtitle' => $offer['subtitle'] ?? null,
                        ];
                    })->values(),
                ],
            ],
        ];
    }
}