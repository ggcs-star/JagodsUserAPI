<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CateringPackage extends BaseModel implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'catering_packages';

    protected $guarded = ['id'];

    protected $casts = [
        'module_id' => 'integer',
        'price' => 'decimal:2',
        'min_guests' => 'integer',
        'max_guests' => 'integer',
        'lead_time_hours' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(
            CateringPackageSection::class,
            'catering_package_id'
        )->orderBy('sort_order', 'asc');
    }

    public function activeSections(): HasMany
    {
        return $this->hasMany(
            CateringPackageSection::class,
            'catering_package_id'
        )
            ->where('status', 1)
            ->orderBy('sort_order', 'asc');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('catering_package_images')
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600);
    }

    public function getCoverImage(): ?Media
    {
        return $this->getMedia('catering_package_images')
            ->first(function (Media $media) {
                return (bool) $media->getCustomProperty(
                    'is_cover',
                    false
                );
            });
    }
}
