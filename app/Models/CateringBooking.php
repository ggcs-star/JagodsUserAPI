<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CateringBooking extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'catering_bookings';

    protected $guarded = ['id'];

    protected $casts = [
        'user_id' => 'integer',
        'module_id' => 'integer',
        'catering_package_id' => 'integer',
        'address_id' => 'integer',

        'package_price' => 'decimal:2',

        'guest_count' => 'integer',

        'event_date' => 'date',
        'event_time' => 'datetime:H:i:s',

        'event_lat' => 'decimal:7',
        'event_long' => 'decimal:7',

        'sub_total' => 'decimal:2',
        'total_amount' => 'decimal:2',

        'status' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CateringPackage::class, 'catering_package_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(
            CateringBookingSection::class,
            'catering_booking_id'
        )->orderBy('sort_order', 'asc');
    }

    public function activeSections(): HasMany
    {
        return $this->hasMany(
            CateringBookingSection::class,
            'catering_booking_id'
        )->orderBy('sort_order', 'asc');
    }
}