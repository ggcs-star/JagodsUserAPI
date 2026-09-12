<?php

namespace App\Models;

use App\Enums\CategoryRequested;
use App\Enums\CategoryStatus;
use App\Models\BaseModel;
use Shipu\Watchable\Traits\WatchableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends BaseModel implements HasMedia
{
    use HasSlug, WatchableTrait, InteractsWithMedia;

    protected $table = 'categories';
    protected $auditColumn = true;
    protected $fillable = ['name', 'slug', 'description', 'status', 'requested', 'parent_id', 'module_id', 'display_module_id', 'category_group_id', 'sort_order','show_on_home'];
    protected $casts = [
        'status' => 'int',
        'requested' => 'int',
        'module_id' => 'int',
        'display_module_id' => 'array',
        'category_group_id' => 'int',
        'show_on_home' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');
    }
    public function getParentNameAttribute()
    {
        return $this->parent?->name ?? '-';
    }
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function getImageAttribute()
    {
        if (!empty($this->getFirstMediaUrl('categories'))) {
            return asset($this->getFirstMediaUrl('categories'));
        }

        return asset('frontend/images/default/category.png');
    }

    public function creator()
    {
        return $this->morphTo();
    }

    public function OnModelCreating()
    {
        $roleID = auth()->user()->myrole ?? 0;
        $this->requested = CategoryRequested::NON_REQUESTED;
        if ($roleID > 1) {
            $this->requested = CategoryRequested::REQUESTED;
            $this->status = CategoryStatus::INACTIVE;
        }
    }

    public function OnModelSaving()
    {
        $roleID = auth()->user()->myrole ?? 0;
        if ($roleID == 1) {
            $this->status = request('status');
        }
    }

    public function getActionButtonAttribute()
    {
        $roleID = auth()->user()->myrole ?? 0;
        if ($roleID > 1) {
            if (($this->creator_id == auth()->id()) && ($this->status == CategoryStatus::INACTIVE)) {
                return true;
            }
            return false;
        }
        return true;
    }

    public function scopeOwner($query)
    {
        $roleID = auth()->user()->myrole ?? 0;
        if ($roleID > 1) {
            $query->where('creator_id', auth()->id());
            $query->where('status', CategoryStatus::INACTIVE);
        }
    }

    public function MenuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'category_menu_items');
    }

    public function getStatusNameAttribute()
    {
        if ($this->status == CategoryStatus::ACTIVE) {
            return '<span class="db-table-badge text-green-600 bg-green-100">' . trans('statuses.' . CategoryStatus::ACTIVE) . '</span>';
        } else if ($this->status == CategoryStatus::INACTIVE) {
            return '<span class="db-table-badge text-red-600 bg-red-100">' . trans('statuses.' . CategoryStatus::INACTIVE) . '</span>';
        }
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function scopeModule($query, $slug)
    {
        return $query->whereHas('module', function ($q) use ($slug) {
            $q->where('slug', $slug);
        });
    }
    public function categoryGroup()
    {
        return $this->belongsTo(CategoryGroup::class);
    }

    public function allMenuItems()
    {
        $categoryIds = Category::where('id', $this->id)
            ->orWhere('parent_id', $this->id)
            ->pluck('id');

        return MenuItem::whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });
    }
    public function displayModule()
    {
        return $this->belongsTo(
            Module::class,
            'display_module_id'
        );
    }
}
