<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class GroceryCategoryDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

            'image' => $this->image,

            'sub_categories' => GrocerySubCategoryResource::collection(
                $this->children
            ),

        ];
    }
}