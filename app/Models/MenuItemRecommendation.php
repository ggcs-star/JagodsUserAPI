<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItemRecommendation extends Model
{
    protected $table = 'menu_item_recommendations';

    protected $fillable = [
        'menu_item_id',
        'recommended_menu_item_id',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'menu_item_id' => 'integer',
        'recommended_menu_item_id' => 'integer',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function recommendedItem()
    {
        return $this->belongsTo(MenuItem::class, 'recommended_menu_item_id');
    }
}