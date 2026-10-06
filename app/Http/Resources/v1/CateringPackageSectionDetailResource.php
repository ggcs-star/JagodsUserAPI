<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringPackageSectionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,

            'catering_package_id' => (int) $this->catering_package_id,

            'name' => $this->name,

            'description' => $this->description ?? '',
            'selection_type' => $this->selection_type,
            'min_selections' => (int) $this->min_selections,

            'max_selections' => (int) $this->max_selections,

            'sort_order' => (int) $this->sort_order,

            'status' => (int) $this->status,

            'images' => $this->getMedia('catering_section_images')
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
            'items' => CateringPackageItemDetailResource::collection(
                $this->whenLoaded('activeItems')
            ),
        ];
    }
}
