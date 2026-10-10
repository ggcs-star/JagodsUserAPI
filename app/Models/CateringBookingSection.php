<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CateringBookingSection extends BaseModel
{
    use HasFactory;

    protected $table = 'catering_booking_sections';

    protected $guarded = ['id'];

    protected $casts = [
        'catering_booking_id' => 'integer',
        'catering_package_section_id' => 'integer',

        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'sort_order' => 'integer',
    ];



    public function booking(): BelongsTo
    {
        return $this->belongsTo(
            CateringBooking::class,
            'catering_booking_id'
        );
    }

    public function packageSection(): BelongsTo
    {
        return $this->belongsTo(
            CateringPackageSection::class,
            'catering_package_section_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            CateringBookingItem::class,
            'catering_booking_section_id'
        )->orderBy('sort_order', 'asc');
    }

    public function selectedItems(): HasMany
    {
        return $this->hasMany(
            CateringBookingItem::class,
            'catering_booking_section_id'
        )
            ->where('is_selected', true)
            ->orderBy('sort_order', 'asc');
    }
}
