<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryGroup extends Model
{
    protected $fillable = [
        'module_id',
        'name',
        'slug',
        'image',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'module_id' => 'int',
        'sort_order' => 'int',
        'status' => 'int',
    ];



    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    // Sirf Main Categories
    public function categories()
    {
        return $this->hasMany(Category::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');;
    }
}
