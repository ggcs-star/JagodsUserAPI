<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLineItem extends Model
{
    protected $table = 'order_line_items';

    protected $fillable = [
        'restaurant_id',
        'order_id',
        'menu_item_id',
        'quantity',
        'unit_price',
        'discounted_price',
        'item_total',
        'menu_item_variation_id',
        'options',
        'options_total',
        'instructions',
    ];

    protected $casts = [
        'restaurant_id' => 'integer',
        'order_id' => 'integer',
        'menu_item_id' => 'integer',
        'quantity' => 'integer',
        'menu_item_variation_id' => 'integer',

        'unit_price' => 'float',
        'discounted_price' => 'float',
        'item_total' => 'float',
        'options_total' => 'float',

        'options' => 'array',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(
            MenuItem::class,
            'menu_item_id',
            'id'
        );
    }

    public function variation()
    {
        return $this->belongsTo(
            MenuItemVariation::class,
            'menu_item_variation_id',
            'id'
        );
    }
}