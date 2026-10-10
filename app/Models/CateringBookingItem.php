<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CateringBookingItem extends BaseModel
{
    use HasFactory;

    protected $table = 'catering_booking_items';

    protected $guarded = ['id'];

    protected $casts = [
        'catering_booking_section_id' => 'integer',
        'catering_package_item_id' => 'integer',
        'menu_item_id' => 'integer',

        'unit_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'extra_price' => 'decimal:2',
        'final_unit_price' => 'decimal:2',
        'item_total' => 'decimal:2',

        'is_default' => 'boolean',
        'is_selected' => 'boolean',

        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];


    public function bookingSection(): BelongsTo
    {
        return $this->belongsTo(
            CateringBookingSection::class,
            'catering_booking_section_id'
        );
    }

    public function packageItem(): BelongsTo
    {
        return $this->belongsTo(
            CateringPackageItem::class,
            'catering_package_item_id'
        );
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(
            MenuItem::class,
            'menu_item_id'
        );
    }
}