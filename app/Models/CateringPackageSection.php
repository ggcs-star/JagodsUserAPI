<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CateringPackageSection extends BaseModel
{
    use HasFactory, SoftDeletes;

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
}
