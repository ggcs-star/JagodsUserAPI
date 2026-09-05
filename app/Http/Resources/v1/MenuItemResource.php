<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'id' => (int) $this->id,

            'name' => $this->name,

            'slug' => $this->slug,

            'module_id' => (int) $this->module_id,

            'menu_number' => $this->menu_number,

            'unit_price' => (float) $this->unit_price,

            'discount_price' => (float) $this->discount_price,

            'final_price' => max(
                0,
                (float) $this->unit_price -
                    (float) $this->discount_price
            ),

            'discount_percentage' =>
            (float) $this->unit_price > 0
                ? round(
                    (
                        (float) $this->discount_price /
                        (float) $this->unit_price
                    ) * 100
                )
                : 0,

            'has_discount' =>
            (float) $this->discount_price > 0,

            'max_cart_quantity' =>
            (int) $this->max_cart_quantity,

            'currency_code' =>
            setting('currency_code'),

            'image' =>
            $this->image,

            'description' =>
            strip_tags($this->description ?? ''),

            'variation_groups' =>
            MenuItemVariationGroupResource::collection(
                $this->whenLoaded('variationGroups')
            ),

            'option_groups' =>
            MenuItemOptionGroupResource::collection(
                $this->whenLoaded('optionGroups')
            ),

            'restroType' =>
            $this->restroType ?? null,

            'tags' => [
                'new',
                'chef-special',
            ],

            'category_id' =>
            $this->categories->pluck('id'),

            'ingredients' =>
            $this->ingredients,
        ];
    }
}
