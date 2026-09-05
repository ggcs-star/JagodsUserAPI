<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemOption extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'menu_item_id',
        'restaurant_id',
        'option_group_id',
        'external_option_id',
        'name',
        'price',
        'attribute',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'menu_item_id' => 'integer',
        'restaurant_id' => 'integer',
        'option_group_id' => 'integer',
        'price' => 'float',
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

    public function optionGroup()
    {
        return $this->belongsTo(
            MenuItemOptionGroup::class,
            'option_group_id'
        );
    }

    public function restaurant()
    {
        return $this->belongsTo(
            Restaurant::class,
            'restaurant_id'
        );
    }
}