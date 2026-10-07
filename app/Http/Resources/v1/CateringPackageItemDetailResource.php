<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringPackageItemDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $menuItem = $this->menuItem;

        return [

            'id' => (int) $this->id,

            'menu_item_id' => (int) $this->menu_item_id,
            'is_default' => (bool) $this->is_default,
            'menu_item' => $menuItem
                ? [
                    'id' => (int) $menuItem->id,

                    'name' => $menuItem->name,

                    'slug' => $menuItem->slug,

                    'module_id' => (int) $menuItem->module_id,

                    'menu_number' => $menuItem->menu_number,

                    'unit_price' => (string) $menuItem->unit_price,

                    'discount_price' => (string) $menuItem->discount_price,

                    'extra_price' => (string) $this->extra_price,

                    'final_price' => max(
                        0,
                        (
                            (float) $menuItem->unit_price -
                            (float) $menuItem->discount_price
                        ) + (float) $this->extra_price
                    ),

                    'discount_percentage' =>
                    (float) $menuItem->unit_price > 0
                        ? round(
                            (
                                (float) $menuItem->discount_price /
                                (float) $menuItem->unit_price
                            ) * 100
                        )
                        : 0,

                    'has_discount' =>
                    (float) $menuItem->discount_price > 0,

                    'max_cart_quantity' =>
                    (int) $menuItem->max_cart_quantity,

                    'currency_code' =>
                    setting('currency_code'),

                    'image' => $menuItem
                        ->getMedia('menu-items')
                        ->map(function ($media) {
                            return [
                                'id' => $media->id,
                                'url' => $media->getUrl(),
                                'file_name' => $media->file_name,
                            ];
                        })
                        ->values()
                        ->toArray(),

                    'cover_image' => $menuItem
                        ->getMedia('menu-item-covers')
                        ->map(function ($media) {
                            return [
                                'id' => $media->id,
                                'url' => $media->getUrl(),
                                'file_name' => $media->file_name,
                            ];
                        })
                        ->values()
                        ->toArray(),
                    'description' =>
                    $menuItem->description ?? '',

                    'description_type' =>
                    $menuItem->description &&
                        $menuItem->description !==
                        strip_tags($menuItem->description)
                        ? 'html'
                        : 'text',

                    'variation_groups' =>
                    MenuItemVariationGroupResource::collection(
                        $menuItem->variationGroups
                    ),

                    'option_groups' =>
                    MenuItemOptionGroupResource::collection(
                        $menuItem->optionGroups
                    ),

                    'restroType' =>
                    $menuItem->restroType ?? null,

                    'tags' =>
                    $menuItem->tags ?? [],

                    'category_id' =>
                    $menuItem->categories->pluck('id'),

                    'ingredients' =>
                    $menuItem->ingredients,
                ]
                : null,
        ];
    }
}
