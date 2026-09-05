<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemVariationGroup extends Model
{
    protected $table = 'menu_item_variation_groups';

    protected $fillable = [
        'menu_item_id',
        'restaurant_id',
        'name',
        'online_display_name',
        'selection_type',
        'is_required',
        'min_selection',
        'max_selection',
        'show_online',
        'show_dinein_qr',
        'allow_open_quantity',
        'max_selection_per_item',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'menu_item_id' => 'integer',
        'restaurant_id' => 'integer',
        'is_required' => 'boolean',
        'min_selection' => 'integer',
        'max_selection' => 'integer',
        'show_online' => 'boolean',
        'show_dinein_qr' => 'boolean',
        'allow_open_quantity' => 'boolean',
        'max_selection_per_item' => 'integer',
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

    public function variations()
    {
        return $this->hasMany(
            MenuItemVariation::class,
            'variation_group_id'
        )->where('status', 1)
         ->orderBy('sort_order');
    }
}