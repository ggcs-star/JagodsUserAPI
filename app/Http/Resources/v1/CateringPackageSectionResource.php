<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringPackageSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'catering_package_id' =>
            $this->catering_package_id,

            'name' => $this->name,

            'description' => $this->description,
            'selection_type' => $this->selection_type,
            'min_selections' =>
            (int) $this->min_selections,

            'max_selections' =>
            (int) $this->max_selections,

            'sort_order' =>
            (int) $this->sort_order,

            'images' => $this->getMedia('catering_package_images')
                ->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'is_cover' => (bool) $media->getCustomProperty(
                            'is_cover',
                            false
                        ),
                    ];
                })
                ->values(),
            'status' =>
            (int) $this->status,

            'items_count' =>
            (int) $this->items_count,
        ];
    }
}
