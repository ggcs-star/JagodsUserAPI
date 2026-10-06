<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $coverImage = $this->getCoverImage();

        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'category_id' => $this->category_id,

            'category' => $this->whenLoaded('category', function () {
                return $this->category ? [
                    'id' => $this->category->id,
                    'title' => $this->category->name,
                    'slug' => $this->category->slug,
                ] : null;
            }),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            'price' => (float) $this->price,
            'price_type' => $this->price_type,

            'min_guests' => $this->min_guests,
            'max_guests' => $this->max_guests,
            'lead_time_hours' => $this->lead_time_hours,

            'cover_image' => $this->getMedia('catering_package_images')
                ->filter(function ($media) {
                    return (bool) $media->getCustomProperty('is_cover', false);
                })
                ->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                    ];
                })
                ->values(),


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

        ];
    }
}
