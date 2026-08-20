<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\CurrentStatus;
use App\Http\Resources\v1\PopularRestaurantResource;
use App\Models\TimeSlot;
use App\Enums\TableStatus;
use App\Models\Restaurant;
use App\Enums\PickupStatus;
use App\Enums\RatingStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Enums\DeliveryStatus;
use App\Enums\RestaurantStatus;
use App\Models\RestaurantRating;
use App\Http\Services\RatingsService;
use App\Http\Services\RestaurantService;
use App\Http\Resources\v1\RatingResource;
use App\Http\Controllers\BackendController;
use App\Http\Resources\v1\MenuItemResource;
use App\Http\Resources\v1\RestaurantResource;
use App\Models\MenuItem;
use App\Models\Category;
use App\Http\Resources\v1\GroceryCategoryResource;
use App\Enums\Module;
use App\Enums\MenuItemStatus;
use App\Enums\CategoryStatus;
use Illuminate\Support\Facades\Log;
class SearchController extends BackendController
{
    use ApiResponse;
    protected $restaurantService;

    public function __construct(RestaurantService $restaurantService)
    {
        parent::__construct();
        $this->data['siteTitle'] = 'Restaurants';
        // $this->middleware('auth:api');
        $this->restaurantService = $restaurantService;
    }
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function index(Request $request)
    // {


    //     if (!blank($request->get('name'))) {
    //         $name = $request->get('name');
    //     }

    //     $expedition = null;
    //     if (!blank($request->get('expedition'))) {
    //         $expedition = $request->get('expedition');
    //     }

    //     try {
    //         $restaurants = $this->getallrestaurant($name, $expedition);

    //         return $this->successResponse(['status' => 200, 'data' => PopularRestaurantResource::collection($restaurants)]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'exception' => get_class($e),
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTrace(),
    //         ]);
    //     }
    // }



    //     public function getallrestaurant($name, $expedition)
// {
//     $queryArray = [];
//     $queryArray['status'] = RestaurantStatus::ACTIVE;
//     $queryArray['current_status'] = CurrentStatus::YES;

    //     if (!blank($expedition)) {
//         if ($expedition == 'delivery') {
//             $queryArray['delivery_status'] = DeliveryStatus::ENABLE;
//         } elseif ($expedition == 'pickup') {
//             $queryArray['pickup_status'] = PickupStatus::ENABLE;
//         } elseif ($expedition == 'table') {
//             $queryArray['table_status'] = TableStatus::ENABLE;
//         }
//     }

    //     $current_time = now()->format('H:i');

    //     $restaurants = Restaurant::where($queryArray)

    //         ->where(function ($q) use ($current_time) {
//             $q->where(function ($q2) use ($current_time) {
//                 $q2->where('opening_time', '>', 'closing_time')
//                   ->where('opening_time', '<', $current_time);
//             })
//             ->orWhere(function ($q3) use ($current_time) {
//                 $q3->where('opening_time', '<', 'closing_time')
//                   ->where('opening_time', '<', $current_time)
//                   ->where('closing_time', '>', $current_time);
//             });
//         });

    //     if (!blank($name)) {
//     $restaurants->where(function ($q) use ($name) {


    //         $q->where('name', 'LIKE', '%' . $name . '%')


    //         ->orWhereHas('menuItems', function ($q2) use ($name) {
//             $q2->where('name', 'LIKE', '%' . $name . '%');
//         });

    //     });
// }

    //     return $restaurants->descending()->get();
// }


    // public function index(Request $request)
// {
//     $name = $request->get('name');
//     $expedition = $request->get('expedition');

    //     try {
//         $restaurants = $this->getallrestaurant($name, $expedition);

    //         return $this->successResponse([
//             'status' => 200,
//             'data' => PopularRestaurantResource::collection($restaurants)
//         ]);

    //     } catch (\Exception $e) {
//         return response()->json([
//             'exception' => get_class($e),
//             'message' => $e->getMessage(),
//             'trace' => $e->getTrace(),
//         ]);
//     }
// }


    // public function getallrestaurant($name, $expedition)
// {
//     $queryArray = [
//         'status' => RestaurantStatus::ACTIVE,
//         'current_status' => CurrentStatus::YES,
//     ];

    //     if (!blank($expedition)) {
//         if ($expedition == 'delivery') {
//             $queryArray['delivery_status'] = DeliveryStatus::ENABLE;
//         } elseif ($expedition == 'pickup') {
//             $queryArray['pickup_status'] = PickupStatus::ENABLE;
//         } elseif ($expedition == 'table') {
//             $queryArray['table_status'] = TableStatus::ENABLE;
//         }
//     }

    //     $current_time = now()->format('H:i');

    //     $restaurants = Restaurant::where($queryArray)
//         ->where(function ($q) use ($current_time) {
//             $q->where(function ($q2) use ($current_time) {
//                 $q2->where('opening_time', '>', 'closing_time')
//                    ->where('opening_time', '<', $current_time);
//             })
//             ->orWhere(function ($q3) use ($current_time) {
//                 $q3->where('opening_time', '<', 'closing_time')
//                    ->where('opening_time', '<', $current_time)
//                    ->where('closing_time', '>', $current_time);
//             });
//         });


    // if (!blank($name)) {

    //     $name = strtolower(trim($name));
//     $short = substr($name, 0, 3);

    //     $restaurants->select('*')
//         ->selectRaw("
//             (
//                 CASE 
//                     WHEN LOWER(name) LIKE ? THEN 100
//                     WHEN LOWER(name) LIKE ? THEN 80
//                     WHEN LOWER(name) LIKE ? THEN 60
//                     WHEN SOUNDEX(name) = SOUNDEX(?) THEN 50
//                     ELSE 10
//                 END
//             ) as relevance
//         ", [
//             "%$name%",    
//             "$name%",     
//             "%$short%",    
//             $name          
//         ])


    //         ->where(function ($q) use ($name, $short) {
//             $q->whereRaw("LOWER(name) LIKE ?", ["%$short%"])
//               ->orWhereHas('menuItems', function ($q2) use ($short) {
//                   $q2->whereRaw("LOWER(name) LIKE ?", ["%$short%"]);
//               });
//         })

    //         ->orderByDesc('relevance');

    // } else {
//     $restaurants->limit(2);
// }

    //     return $restaurants->latest()->get(); 
// }


    // public function globalSearch1(Request $request)
// {
//     try {
//         $query = $request->input('query', ''); 
//         $lat = $request->input('lat'); 
//         $lng = $request->input('lng');
//         $radius = 10000; 

    //         $restaurantSearch = Restaurant::search($query);

    //         if ($lat && $lng) {
//             $restaurantSearch->options([
//                 'filter' => "_geoRadius({$lat}, {$lng}, {$radius})",
//                 'sort' => ["_geoPoint({$lat}, {$lng}):asc", "total_orders:desc"]
//             ]);
//         } else {
//             $restaurantSearch->orderBy('total_orders', 'desc');
//         }

    //         $restaurantSearch->query(function ($query) {
//             $query->with(['media']); 
//         });

    //         $restaurants = $restaurantSearch->take(15)->get();

    //         return response()->json([
//             'status' => 200,
//             'message' => 'Search completed successfully.',
//             'data' => PopularRestaurantResource::collection($restaurants)
//         ], 200);

    //     } catch (\Exception $e) {
//         \Illuminate\Support\Facades\Log::error('Meilisearch Global Search Error: ' . $e->getMessage());
//         return response()->json([
//             'status' => 500,
//             'message' => 'Failed to execute search. Please try again later.',
//             'error' => config('app.env') !== 'production' ? $e->getMessage() : null
//         ], 500);
//     }
// }


    public function globalSearch(Request $request)
    {
        try {

            $request->validate([
                'slug' => [
                    'required',
                    'string',
                    'in:' . implode(',', Module::all()),
                ],

                'search' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'page' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'per_page' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'lat' => [
                    'nullable',
                    'string',
                ],

                'lng' => [
                    'nullable',
                    'string',
                ],
            ]);

            $slug = strtolower(trim($request->input('slug')));
            $search = trim($request->input('search', ''));
            $perPage = (int) $request->input('per_page', 10);

            if ($slug === Module::YOUR_CITY_SLUG) {

                if ($search !== '') {

                    $restaurantSearch = Restaurant::search($search);

                    $restaurantSearch->query(function ($query) {

                        $query
                            ->where('module_id', Module::YOUR_CITY)
                            ->where('status', RestaurantStatus::ACTIVE)
                            ->with('media');
                    });

                    if ($request->filled('lat') && $request->filled('lng')) {

                        $lat = trim($request->input('lat'));
                        $lng = trim($request->input('lng'));

                        $restaurantSearch->options([
                            'filter' => 'module_id = ' . Module::YOUR_CITY
                                . ' AND status = ' . RestaurantStatus::ACTIVE,

                            'sort' => [
                                "_geoPoint({$lat}, {$lng}):asc",
                                'total_orders:desc',
                            ],
                        ]);
                    }

                    $restaurants = $restaurantSearch->paginate($perPage);

                    return $this->successPaginationResponse(
                        message: 'Restaurant search results fetched successfully.',
                        paginator: $restaurants,
                        data: PopularRestaurantResource::collection(
                            $restaurants->items()
                        )
                    );
                }

                $query = Restaurant::query()
                    ->where('module_id', Module::YOUR_CITY)
                    ->where('status', RestaurantStatus::ACTIVE)
                    ->with('media');


                if ($request->filled('lat') && $request->filled('lng')) {

                    $lat = trim($request->input('lat'));
                    $lng = trim($request->input('lng'));

                    $query
                        ->selectRaw(
                            "
            restaurants.*,
            (
                6371000 * acos(
                    cos(radians(?)) *
                    cos(radians(lat)) *
                    cos(radians(`long`) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(lat))
                )
            ) AS distance
            ",
                            [
                                $lat,
                                $lng,
                                $lat,
                            ]
                        )
                        ->orderBy('distance', 'asc')
                        ->orderByDesc('total_orders');

                } else {


                    $query->orderByDesc('total_orders');
                }

                $restaurants = $query->paginate($perPage);

                return $this->successPaginationResponse(
                    message: 'Restaurants fetched successfully.',
                    paginator: $restaurants,
                    data: PopularRestaurantResource::collection(
                        $restaurants->items()
                    )
                );
            }

            if ($slug === Module::ALL_OVER_INDIA_SLUG) {

                if ($search !== '') {

                    $menuSearch = MenuItem::search($search);

                    $menuSearch->query(function ($query) {

                        $query
                            ->where('module_id', Module::ALL_OVER_INDIA)
                            ->where('status', MenuItemStatus::ACTIVE)
                            ->with([
                                'categories',
                                'media',
                                'variations',
                                'options',
                            ]);
                    });

                    $matchedItems = $menuSearch
                        ->take(1000)
                        ->get();

                    $categoryIds = $matchedItems
                        ->flatMap(function ($item) {
                            return $item->categories->pluck('id');
                        })
                        ->unique()
                        ->values();

                    if ($categoryIds->isEmpty()) {

                        $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                            [],
                            0,
                            $perPage,
                            (int) $request->input('page', 1),
                            [
                                'path' => $request->url(),
                                'query' => $request->query(),
                            ]
                        );

                        return $this->successPaginationResponse(
                            message: 'No grocery items found.',
                            paginator: $emptyPaginator,
                            data: []
                        );
                    }

                    $categories = Category::query()
                        ->where('module_id', Module::ALL_OVER_INDIA)
                        ->whereIn('id', $categoryIds)
                        ->where('status', CategoryStatus::ACTIVE)
                        ->with('categoryGroup:id,name')
                        ->get();

                    foreach ($categories as $category) {

                        $categoryIdsForItems = Category::query()
                            ->where('id', $category->id)
                            ->orWhere('parent_id', $category->id)
                            ->pluck('id');

                        $items = MenuItem::with([
                            'media',
                            'categories',
                            'variations',
                            'options',
                        ])
                            ->whereHas('categories', function ($q) use ($categoryIdsForItems) {
                                $q->whereIn(
                                    'categories.id',
                                    $categoryIdsForItems
                                );
                            })
                            ->where('module_id', Module::ALL_OVER_INDIA)
                            ->where('status', MenuItemStatus::ACTIVE)
                            ->get();

                        $matchedIds = $matchedItems
                            ->whereIn('id', $items->pluck('id'))
                            ->pluck('id')
                            ->values()
                            ->toArray();

                        $items = $items
                            ->sortBy(function ($item) use ($matchedIds) {

                                $index = array_search(
                                    $item->id,
                                    $matchedIds,
                                    true
                                );

                                return $index === false
                                    ? PHP_INT_MAX
                                    : $index;
                            })
                            ->values();

                        $category->items = $items;
                    }

                    $currentPage = (int) $request->input('page', 1);

                    $paginatedCategories = new \Illuminate\Pagination\LengthAwarePaginator(
                        $categories
                            ->forPage($currentPage, $perPage)
                            ->values(),
                        $categories->count(),
                        $perPage,
                        $currentPage,
                        [
                            'path' => $request->url(),
                            'query' => $request->query(),
                        ]
                    );

                    return $this->successPaginationResponse(
                        message: 'Grocery search results fetched successfully.',
                        paginator: $paginatedCategories,
                        data: GroceryCategoryResource::collection(
                            $paginatedCategories->items()
                        )
                    );
                }

                $query = Category::query()
                    ->select([
                        'id',
                        'category_group_id',
                        'name',
                        'slug',
                        'module_id',
                        'parent_id',
                    ])
                    ->with('categoryGroup:id,name')
                    ->where('module_id', Module::ALL_OVER_INDIA)
                    ->whereNull('parent_id')
                    ->where('status', CategoryStatus::ACTIVE)
                    ->orderBy('name');

                $categories = $query->paginate($perPage);

                foreach ($categories as $category) {

                    $categoryIds = Category::query()
                        ->where('id', $category->id)
                        ->orWhere('parent_id', $category->id)
                        ->pluck('id');

                    $category->items = MenuItem::with([
                        'media',
                        'categories',
                        'variations',
                        'options',
                    ])
                        ->whereHas('categories', function ($q) use ($categoryIds) {
                            $q->whereIn(
                                'categories.id',
                                $categoryIds
                            );
                        })
                        ->where('module_id', Module::ALL_OVER_INDIA)
                        ->where('status', MenuItemStatus::ACTIVE)
                        ->limit(10)
                        ->get();
                }

                return $this->successPaginationResponse(
                    message: 'Grocery categories fetched successfully.',
                    paginator: $categories,
                    data: GroceryCategoryResource::collection(
                        $categories->items()
                    )
                );
            }

            return $this->errorResponse(
                message: 'Invalid module.'
            );

        } catch (\Throwable $e) {

            Log::error('Global Search Error', [
                'slug' => $request->input('slug'),
                'search' => $request->input('search'),
                'page' => $request->input('page'),
                'per_page' => $request->input('per_page'),
                'lat' => $request->input('lat'),
                'lng' => $request->input('lng'),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }
}
