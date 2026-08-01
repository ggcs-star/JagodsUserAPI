<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BackendController;
use App\Traits\ApiResponse;
class HomeConfigController extends BackendController
{
     use ApiResponse;
    public function index()
    {
        $data = [
            'default_tab' => 'jagods',

            'headers' => [

                [
                    'id' => 1,
                    'name' => 'Jagods',
                    'slug' => 'jagods',
                    'selected' => true,

                    'badge' => [
                        'text' => 'Your City',
                        'background_color' => '#FFD54F',
                        'text_color' => '#1E1E1E',
                        'border_color' => '#F9A825',
                        'gradient' => [
                            '#FFE082',
                            '#FFD54F',
                        ],
                    ],

                    'icon_url' => 'https://images.jagods.com/banner/banner.svg',

                    'promo_banner' => [
                        'type' => 'gif',
                        'image_url' => 'https://images.jagods.com/banner/banner.svg',

                        'action' => [
                            'type' => 'navigation',
                            'navigation_type' => 'category',
                            'value' => 'fresh-vegetables',

                            'params' => [
                                'category_id' => 15,
                            ],
                        ],
                    ],

                    'color_detail' => [
                        'gradient' => [
                            'web' => [
                                '#FFEED4',
                                '#FFFFFF',
                            ],

                            'mobile' => [
                                '#FFE9C6',
                                '#FFF9F0',
                            ],
                        ],

                        'icon_color' => '#FFE9C6',
                        'border_color' => '#F58626',
                        'background_color' => '#FFF9F0',
                    ],

                    'search_placeholder' => 'Search for products, brands, or categories',

                    'bottom_navigation' => [

                        [
                            'id' => 1,
                            'title' => 'Home',
                            'slug' => 'home',
                            'icon_url' => 'https://images.jagods.com/banner/banner.svg',
                            'selected_icon_url' => 'https://images.jagods.com/banner/banner.svg',

                            'redirection' => [
                                'navigation_type' => 'home',
                            ],
                        ],

                        [
                            'id' => 2,
                            'title' => 'Reels',
                            'slug' => 'reels',
                            'icon_url' => 'https://images.jagods.com/banner/banner.svg',
                            'selected_icon_url' => 'https://images.jagods.com/banner/banner.svg',

                            'redirection' => [
                                'navigation_type' => 'reels',
                            ],
                        ],

                        [
                            'id' => 3,
                            'title' => 'Cart',
                            'slug' => 'cart',
                            'icon_url' => 'https://images.jagods.com/banner/banner.svg',
                            'selected_icon_url' => 'https://images.jagods.com/banner/banner.svg',
                            'badge_count' => 1,

                            'redirection' => [
                                'navigation_type' => 'cart',
                            ],
                        ],

                    ],

                    'other_details' => [
                        'cta' => 'Start Shopping',
                        'text' => 'Full marketplace',

                        'service_details' => [
                            'Electronics',
                            'Fashion',
                            'Home',
                            '+ More',
                        ],
                    ],
                ],

                [
                    'id' => 2,
                    'name' => 'Grocery',
                    'slug' => 'grocery',
                    'selected' => false,

                    'badge' => [
                        'text' => 'All Over India',
                        'background_color' => '#D32F2F',
                        'text_color' => '#FFFFFF',
                        'border_color' => '#B71C1C',

                        'gradient' => [
                            '#EF5350',
                            '#D32F2F',
                        ],
                    ],

                    'icon_url' => 'https://images.jagods.com/banner/banner.svg',

                    'promo_banner' => [
                        'type' => 'gif',
                        'image_url' => 'https://images.jagods.com/banner/banner.svg',

                        'action' => [
                            'type' => 'navigation',
                            'navigation_type' => 'category',
                            'value' => 'daily-grocery',

                            'params' => [
                                'category_id' => 25,
                            ],
                        ],
                    ],

                    'color_detail' => [
                        'gradient' => [
                            'web' => [
                                '#FFD6D6',
                                '#FFFFFF',
                            ],

                            'mobile' => [
                                '#FFD6D6',
                                '#FFF5F5',
                            ],
                        ],

                        'icon_color' => '#FFD6D6',
                        'border_color' => '#BC2828',
                        'background_color' => '#FFF5F5',
                    ],

                    'search_placeholder' => 'Search items for quick delivery',

                    'bottom_navigation' => [

                        [
                            'id' => 1,
                            'title' => 'Home',
                            'slug' => 'home',
                            'icon_url' => 'https://images.jagods.com/banner/banner.svg',
                            'selected_icon_url' => 'https://images.jagods.com/banner/banner.svg',

                            'redirection' => [
                                'navigation_type' => 'home',
                            ],
                        ],

                        [
                            'id' => 2,
                            'title' => 'Categories',
                            'slug' => 'categories',
                            'icon_url' => 'https://images.jagods.com/banner/banner.svg',
                            'selected_icon_url' => 'https://images.jagods.com/banner/banner.svg',

                            'redirection' => [
                                'navigation_type' => 'categories',
                            ],
                        ],

                        [
                            'id' => 3,
                            'title' => 'Cart',
                            'slug' => 'cart',
                            'icon_url' => 'https://images.jagods.com/banner/banner.svg',
                            'selected_icon_url' => 'https://images.jagods.com/banner/banner.svg',
                            'badge_count' => 1,

                            'redirection' => [
                                'navigation_type' => 'cart',
                            ],
                        ],

                    ],

                    'other_details' => [
                        'cta' => 'Order Now',
                        'text' => '1 hour - 2 hour delivery',

                        'service_details' => [
                            'Groceries',
                            'Snacks',
                            'Medicines',
                            '+ More',
                        ],
                    ],
                ],

            ],
        ];

        return $this->successResponse(
            message: 'Global header retrieved successfully.',
            data: $data
        );
    }
}