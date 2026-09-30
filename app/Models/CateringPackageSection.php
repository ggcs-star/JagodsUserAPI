<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\InteractsWithMedia;

class CateringPackageSection extends BaseModel implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'catering_package_sections';

    protected $guarded = ['id'];

    protected $casts = [
        'catering_package_id' => 'integer',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(
            CateringPackage::class,
            'catering_package_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            CateringPackageItem::class,
            'catering_package_section_id'
        )->orderBy('sort_order', 'asc');
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(
            CateringPackageItem::class,
            'catering_package_section_id'
        )
            ->where('status', 1)
            ->orderBy('sort_order', 'asc');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('catering_section_images')
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
        return $this->getMedia('catering_section_images')
            ->first(function (Media $media) {
                return (bool) $media->getCustomProperty(
                    'is_cover',
                    false
                );
            });
    }
}
