<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = [
        'device_type',
        'version',
        'minimum_supported_version',
        'platform',
        'whats_new',
        'app_storage_url',
        'is_active',
        'released_at',
    ];

    protected $casts = [
        'device_type' => 'integer',
        'is_active' => 'boolean',
        'released_at' => 'datetime',
    ];
}