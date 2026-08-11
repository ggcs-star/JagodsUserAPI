<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends BaseModel
{
    protected $table       = 'addresses';
    protected $fillable    = ['label', 'address', 'user_id','pincode','receiver_name','receiver_phone','is_default'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
