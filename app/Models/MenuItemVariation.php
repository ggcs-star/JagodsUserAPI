<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemVariation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'menu_item_id',
        'restaurant_id',
        'variation_group_id',
        'name',
        'price',
        'discount_price',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'menu_item_id' => 'integer',
        'restaurant_id' => 'integer',
        'variation_group_id' => 'integer',
        'price' => 'float',
        'discount_price' => 'float',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    public function menuItem()
    {
        return $this->belongsTo(
            MenuItem::class,
            'menu_item_id'
        );
    }

    public function variationGroup()
    {
        return $this->belongsTo(
            MenuItemVariationGroup::class,
            'variation_group_id'
        );
    }
}