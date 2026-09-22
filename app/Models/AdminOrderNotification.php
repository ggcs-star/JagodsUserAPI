<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminOrderNotification extends Model
{
    protected $table = 'admin_order_notifications';

    protected $fillable = [
        'order_id',
        'restaurant_id',
        'order_code',
        'title',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}