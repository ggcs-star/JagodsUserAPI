<?php


namespace App\Http\Services;

use App\Enums\CuisinesStatus;
use App\Models\Cuisine;
use Illuminate\Http\Request;

class CuisineService
{
    public function allCuisines(Request $request)
{
    $perPage = (int) $request->get('per_page', 10);

    $query = Cuisine::where('status', CuisinesStatus::ACTIVE);

    if ($request->filled('id')) {

        $search = trim($request->id);

        $query->where(function ($q) use ($search) {

            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");

        });
    }

    return $query
        ->descending()
        ->paginate($perPage);
}
    public function show($id)
    {
        return Cuisine::find($id);
       
    }

    public function store(Request $request)
    {
        $cuisine              = new Cuisine;
        $cuisine->name        = $request->name;
        $cuisine->description = $request->description;
        $cuisine->parent_id   = 0;
        $cuisine->depth       = 0;
        $cuisine->left        = 0;
        $cuisine->right       = 0;
        $cuisine->status      = $request->status;
        $cuisine->save();

        return $cuisine;
    }

    public function media($cuisine)
    {
        if (!blank(request()->input('image'))) {
            $cuisine->addMediaFromRequest('image')->toMediaCollection('cuisines');
        }
    }

    public function update(Request $request, $cuisine) : void
    {
        $cuisine->name        = $request->name;
        $cuisine->description = $request->description;
        $cuisine->parent_id   = 0;
        $cuisine->depth       = 0;
        $cuisine->left        = 0;
        $cuisine->right       = 0;
        $cuisine->save();
    }
    public function updateMedia($cuisine)
    {
        if (!blank(request()->input('image'))) {
            $cuisine->media()->delete();
            $cuisine->addMediaFromRequest('image')->toMediaCollection('categories');
        }
    }
    public function delete($table)
    {
        $table->delete();
    }

}
