<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends BaseModel
{
    protected $table       = 'addresses';
    protected $fillable = [
    'label',
    'label_name',
    'address',
    'apartment',
    'landmark',
    'city',
    'state',
    'country',
    'user_id',
    'pincode',
    'receiver_name',
    'receiver_phone',
    'latitude',
    'longitude',
    'is_default',
];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
