<?php

namespace App\Models;

use App\Models\BaseModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Banner extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'short_description',
        'link',
        'sort',
        'status',
        'target_type',
        'target_id',
        'show_on_landing',
    ];

    protected $casts = [
        'status' => 'integer',
        'target_id' => 'integer',
        'show_on_landing' => 'integer',
    ];

    protected $auditColumn = true;

    public function getImageAttribute()
    {
        if (!empty($this->getFirstMediaUrl('banner'))) {
            return asset($this->getFirstMediaUrl('banner'));
        }

        return asset('backend/images/default/banner.jpg');
    }

    public function target()
    {
        return $this->morphTo();
    }
}