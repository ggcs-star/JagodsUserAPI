<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemOptionGroupResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,

            'menu_item_id' =>
                (int) $this->menu_item_id,

            'restaurant_id' =>
                (int) $this->restaurant_id,

            'external_group_id' =>
                $this->external_group_id,

            'name' =>
                $this->name,

            'online_display_name' =>
                $this->online_display_name,

            'selection_type' =>
                $this->selection_type,

            'is_required' =>
                (bool) $this->is_required,

            'min_selection' =>
                (int) $this->min_selection,

            'max_selection' =>
                (int) $this->max_selection,

            'max_selection_per_item' =>
                (int) $this->max_selection_per_item,

            'show_online' =>
                (bool) $this->show_online,

            'show_dinein_qr' =>
                (bool) $this->show_dinein_qr,

            'allow_open_quantity' =>
                (bool) $this->allow_open_quantity,

            'sort_order' =>
                (int) $this->sort_order,

            'status' =>
                (int) $this->status,

            'options' =>
                MenuItemOptionResource::collection(
                    $this->whenLoaded('options')
                ),
        ];
    }
}