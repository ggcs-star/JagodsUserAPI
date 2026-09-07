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

        'menu_item_name',
        'variation_group_id',
        'variation_group_name',
        'variation_name',
        'variation_price',
        'variation_discount_price',
        'final_unit_price',
    ];

    protected $casts = [
        'restaurant_id' => 'int',
        'product_id' => 'int',
        'order_id' => 'int',
        'quantity' => 'int',
        'menu_item_variation_id' => 'int',
        'menu_item_id' => 'int',
        'options_total' => 'int',

        'variation_group_id' => 'int',
        'variation_price' => 'decimal:2',
        'variation_discount_price' => 'decimal:2',
        'final_unit_price' => 'decimal:2',
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
