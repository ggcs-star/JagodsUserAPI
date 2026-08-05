<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function categoryGroups()
{
    return $this->hasMany(CategoryGroup::class);
}
}