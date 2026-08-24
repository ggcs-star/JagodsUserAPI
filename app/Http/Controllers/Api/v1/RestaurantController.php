<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Order;
use App\Models\Coupon;
use App\Models\Discount;
use App\Models\TimeSlot;
use App\Enums\OrderStatus;
use App\Models\Restaurant;
use App\Enums\RatingStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Enums\DiscountStatus;
use App\Enums\MenuItemStatus;
use App\Models\RestaurantRating;
use App\Http\Services\RatingsService;
use App\Http\Services\RestaurantService;
use App\Http\Resources\v1\CouponResource;
use App\Http\Resources\v1\RatingResource;
use App\Http\Controllers\BackendController;
use App\Http\Resources\v1\MenuItemResource;
use App\Http\Resources\v1\RestaurantResource;
use App\Models\MenuItem;
use App\Http\Resources\v1\RestaurantBannerResource;
use App\Enums\Module;
class RestaurantController extends BackendController
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
    public function index(Request $request)
    {
        try {

            $id = $request->input('id');
            $status = $request->input('status');
            $applied = $request->input('applied');

            $restaurants = $this->restaurantService->getallrestaurant($id, $status, $applied);

            return $this->successResponse(
                message: 'Restaurant list fetched successfully',
                data: RestaurantResource::collection($restaurants)
            );

        } catch (\Exception $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }


    // public function show($id)
    // {
    //     $this->data['restaurant'] = Restaurant::findOrFail($id);
    //     // dd($this->data['restaurant']);
    //     $rating                   = new RatingsService();
    //     $ratingArray              = $rating->avgRating($this->data['restaurant']->id);
    //     $RestaurantRatings        = RestaurantRating::where(['restaurant_id' => $this->data['restaurant']->id, 'status' => RatingStatus::ACTIVE])->get();
    //     $this->data['timeSlots']  = TimeSlot::where(['restaurant_id' => $this->data['restaurant']->id])->get();

    //     $this->data['restaurant'] = new RestaurantResource($this->data['restaurant']);
    //     $this->data['menuItems'] = MenuItemResource::collection(
    //     $this->data['restaurant']->menuItems()->where('status', 5)->get());
    //     $this->data['reviews']    = RatingResource::collection($RestaurantRatings);
    //     $this->data['countUser']  = $ratingArray['countUser'];
    //     $this->data['avgRating']  = $ratingArray['avgRating'];



    //     $this->data['vouchers'] = [];
    //     // $today = date('Y-m-d h:i:s');
    //     // $vouchers = Coupon::whereDate('to_date', '>', $today)
    //     //     ->where('restaurant_id', '=', $this->data['restaurant']->id)
    //     //     ->whereDate('from_date', '<', $today)
    //     //     ->where('limit', '>', 0)->get();
    //     // if (!blank($vouchers)) {
    //     //     $data = [];
    //     //     foreach ($vouchers as $voucher) {
    //     //         $total_used = Discount::where('coupon_id', $voucher->id)->where('status', \App\Enums\DiscountStatus::ACTIVE)->count();
    //     //         if ($total_used < $voucher->limit) {
    //     //             $data[] = $voucher;
    //     //         }
    //     //     }
    //     //     if (!blank($data)) {
    //     //         $this->data['vouchers']         = CouponResource::collection($data);
    //     //     }
    //     // }

    //     if (auth()->user()) {
    //         $order = Order::where([
    //             'restaurant_id' => $id,
    //             'status'        => OrderStatus::COMPLETED,
    //             'user_id'       => auth()->user()->id
    //         ])->get();
    //     } else {
    //         $order = [];
    //     }
    //     $this->data['order_status']        = !blank($order);

    //     try {
    //         return $this->successResponse(['status' => 200, 'data' => $this->data]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'exception' => get_class($e),
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTrace(),
    //         ]);
    //     }
    // }

    public function show(Request $request)
    {
        // dd("df");
        try {

            $id = $request->input('id');

            $restaurant = Restaurant::with([
                'banners'
            ])->findOrFail($id);

            $rating = new RatingsService();
            $ratingArray = $rating->avgRating($restaurant->id);

            $categoriesData = \App\Models\Category::whereHas('menuItems', function ($query) use ($id) {
                $query->where('restaurant_id', $id)
                    ->where('status', MenuItemStatus::ACTIVE);
            })
                ->select('id', 'name')
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'image' => $category->image,
                    ];
                })
                ->values();

            $restaurantRatings = RestaurantRating::where([
                'restaurant_id' => $restaurant->id,
                'status' => RatingStatus::ACTIVE,
            ])->get();

            $timeSlots = TimeSlot::where('restaurant_id', $restaurant->id)->get();

            $this->data['restaurant'] = new RestaurantResource($restaurant);
            $this->data['banners'] = RestaurantBannerResource::collection($restaurant->banners);
            $this->data['categories'] = $categoriesData;

            // Menu Items removed (Separate API)
            // $this->data['menuItems'] = MenuItemResource::collection($restaurant->menuItems);

            $this->data['reviews'] = RatingResource::collection($restaurantRatings);
            $this->data['timeSlots'] = $timeSlots;
            $this->data['countUser'] = $ratingArray['countUser'];
            $this->data['avgRating'] = $ratingArray['avgRating'];
            $this->data['vouchers'] = [];
            $this->data['order_status'] = true;

            return $this->successResponse(
                message: 'Restaurant details fetched successfully.',
                data: $this->data
            );

        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }
    public function menuItems(Request $request)
    {
        try {

            $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'category_id' => 'nullable|exists:categories,id',
                'search' => 'nullable|string|max:100',
                'sort_by' => 'nullable|in:popularity,new_arrivals,price_low_high,price_high_low,discount_high_low',
            ]);

            $perPage = $request->input('per_page', 10);
            $search = trim($request->input('search', ''));

            $query = MenuItem::query()
                ->where('restaurant_id', $request->restaurant_id)
                ->where('module_id', Module::YOUR_CITY)
                ->where('status', MenuItemStatus::ACTIVE);

            if ($request->filled('category_id')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('categories.id', $request->category_id);
                });
            }

            if ($search !== '') {
                $query = $this->applyFuzzyMenuSearch(
                    query: $query,
                    restaurantId: $request->restaurant_id,
                    search: $search
                );
            }

            switch ($request->sort_by) {

                case 'popularity':
                    $query->orderByDesc('counter');
                    break;

                case 'new_arrivals':
                    $query->latest();
                    break;

                case 'price_low_high':
                    $query->orderBy('unit_price', 'asc');
                    break;

                case 'price_high_low':
                    $query->orderBy('unit_price', 'desc');
                    break;

                case 'discount_high_low':
                    $query->orderByRaw("
                    CASE
                        WHEN unit_price > 0
                        THEN ((unit_price - discount_price) / unit_price) * 100
                        ELSE 0
                    END DESC
                ");
                    break;

                default:
                    $query->latest();
                    break;
            }

            $menuItems = $query->paginate($perPage);

            $suggestions = [];

            if ($search !== '') {
                $suggestions = $this->getMenuItemSuggestions(
                    restaurantId: $request->restaurant_id,
                    search: $search
                );
            }

            return $this->successPaginationResponse(
                message: 'Menu items fetched successfully.',
                paginator: $menuItems,
                data: [
                    'suggestions' => $suggestions,
                    'items' => MenuItemResource::collection($menuItems->items()),
                ]
            );

        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                ? $e->getMessage()
                : 'Internal Server Error'
            );
        }
    }
    private function applyFuzzyMenuSearch($query, int $restaurantId, string $search)
    {
        $search = strtolower(trim($search));

        $normalQuery = clone $query;

        $normalQuery->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhereRaw(
                    "MATCH(name, description) AGAINST(? IN BOOLEAN MODE)",
                    [$search]
                );
        });

        if ($normalQuery->exists()) {
            return $normalQuery;
        }

        $menuNames = MenuItem::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', MenuItemStatus::ACTIVE)
            ->whereNotNull('name')
            ->select('name')
            ->distinct()
            ->pluck('name');

        $matchedNames = [];

        foreach ($menuNames as $menuName) {

            $menuNameLower = strtolower(trim($menuName));

            $bestScore = $this->calculateFuzzyScore(
                $search,
                $menuNameLower
            );

            $words = preg_split('/\s+/', $menuNameLower);

            foreach ($words as $word) {

                if (strlen($word) < 3) {
                    continue;
                }

                $wordScore = $this->calculateFuzzyScore(
                    $search,
                    $word
                );

                $bestScore = max(
                    $bestScore,
                    $wordScore
                );
            }

            if ($bestScore >= $this->getFuzzyThreshold($search)) {

                $matchedNames[] = [
                    'name' => $menuName,
                    'score' => $bestScore,
                ];
            }
        }

        usort($matchedNames, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $matchedNames = array_slice($matchedNames, 0, 10);

        $names = array_column($matchedNames, 'name');

        if (empty($names)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('name', $names);
    }
    private function calculateFuzzyScore(string $search, string $value): float
    {
        if ($search === '' || $value === '') {
            return 0;
        }

        if ($search === $value) {
            return 100;
        }

        if (str_contains($value, $search)) {
            return 95;
        }

        $distance = levenshtein($search, $value);

        $maxLength = max(
            strlen($search),
            strlen($value)
        );

        if ($maxLength === 0) {
            return 0;
        }

        return (
            1 - ($distance / $maxLength)
        ) * 100;
    }

    private function getMenuItemSuggestions(
        int $restaurantId,
        string $search
    ): array {

        $search = strtolower(trim($search));

        $menuNames = MenuItem::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', MenuItemStatus::ACTIVE)
            ->whereNotNull('name')
            ->select('name')
            ->distinct()
            ->pluck('name');

        $suggestions = [];

        foreach ($menuNames as $menuName) {

            $menuNameLower = strtolower(trim($menuName));

            $bestScore = $this->calculateFuzzyScore(
                $search,
                $menuNameLower
            );

            foreach (preg_split('/\s+/', $menuNameLower) as $word) {

                if (strlen($word) < 3) {
                    continue;
                }

                $wordScore = $this->calculateFuzzyScore(
                    $search,
                    $word
                );

                $bestScore = max(
                    $bestScore,
                    $wordScore
                );
            }

            if ($bestScore >= $this->getFuzzyThreshold($search)) {

                $suggestions[] = [
                    'name' => $menuName,
                    'score' => $bestScore,
                ];
            }
        }

        usort($suggestions, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return collect(array_slice($suggestions, 0, 10))
            ->pluck('name')
            ->values()
            ->toArray();
    }
    private function getFuzzyThreshold(string $search): int
    {
        $length = strlen($search);

        return match (true) {
            $length <= 2 => 80,
            $length === 3 => 70,
            $length === 4 => 65,
            $length >= 5 => 55,
            default => 60,
        };
    }


}
