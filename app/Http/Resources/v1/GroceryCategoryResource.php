<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroceryCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

            'image' => $this->image,

            'category_group' => [
                'id'   => $this->categoryGroup?->id,
                'name' => $this->categoryGroup?->name,
            ],

            'items' => MenuItemResource::collection(
                $this->items ?? collect()
            ),

        ];
    }
}