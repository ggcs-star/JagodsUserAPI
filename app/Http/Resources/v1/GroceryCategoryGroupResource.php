<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroceryCategoryGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,

            'categories' => GroceryCategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}