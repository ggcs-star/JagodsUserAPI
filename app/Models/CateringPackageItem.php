<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CateringPackageItem extends BaseModel
{
    use HasFactory;

    protected $table = 'catering_package_items';

    protected $guarded = ['id'];

    protected $casts = [
        'catering_package_section_id' => 'integer',
        'menu_item_id' => 'integer',
        'extra_price' => 'decimal:2',
        'sort_order' => 'integer',
        'status' => 'integer',
        'is_default' => 'boolean',
    ];

  
    public function section(): BelongsTo
    {
        return $this->belongsTo(
            CateringPackageSection::class,
            'catering_package_section_id'
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