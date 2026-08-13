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
                        'icon_url' => 'https://images.jagods.in/landing/Your%20City.svg',
                        'background_color' => '#FFD54F',
                        'text_color' => '#1E1E1E',
                        'border_color' => '#F9A825',
                        'gradient' => [
                            '#FFE082',
                            '#FFD54F',
                        ],
                    ],

                    'icon_url' => 'https://images.jagods.in/landing/Tab%201.svg',

                    'promo_banner' => [
                        'type' => 'gif',
                        'image_url' => 'https://images.jagods.in/landing/banner.svg',

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
                                '#427C6D',
                                '#7CB290',
                                
                            ],
                        ],

                        'icon_color' => '#FFE9C6',
                        'border_color' => '#F58626',
                        'background_color' => '#427C6D',
                    ],

                    'search_placeholder' => 'Search for products, "Vadapav"',

                    'bottom_navigation' => [

                        [
                            'id' => 1,
                            'title' => 'Home',
                            'slug' => 'home',
                            'icon_url' => 'https://images.jagods.in/landing/Home.svg',
                            'selected_icon_url' => 'https://images.jagods.in/landing/home_fill.svg',

                            'redirection' => [
                                'navigation_type' => 'home',
                            ],
                        ],

                        [
                            'id' => 2,
                            'title' => 'Reels',
                            'slug' => 'reels',
                            'icon_url' => 'https://images.jagods.in/landing/Reel.svg',
                            'selected_icon_url' => 'https://images.jagods.in/landing/Reel_fill.svg',

                            'redirection' => [
                                'navigation_type' => 'reels',
                            ],
                        ],

                        [
                            'id' => 3,
                            'title' => 'Cart',
                            'slug' => 'cart',
                            'icon_url' => 'https://images.jagods.in/landing/Cart.svg',
                            'selected_icon_url' => 'https://images.jagods.in/landing/Cart_fill.svg',
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
                        'icon_url' => 'https://images.jagods.in/landing/All%20Over%20India.svg',
                        'background_color' => '#D32F2F',
                        'text_color' => '#FFFFFF',
                        'border_color' => '#B71C1C',

                        'gradient' => [
                            '#EF5350',
                            '#D32F2F',
                        ],
                    ],

                    'icon_url' => 'https://images.jagods.in/landing/Tab%202.svg',

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
                                '#7AA2C5',
                                '#95BAD4',
                            ],
                        ],

                        'icon_color' => '#FFD6D6',
                        'border_color' => '#BC2828',
                        'background_color' => '#7AA2C5',
                    ],

                    'search_placeholder' => 'Search for products, "Snacks"',

                    'bottom_navigation' => [

                        [
                            'id' => 1,
                            'title' => 'Home',
                            'slug' => 'home',
                            'icon_url' => 'https://images.jagods.in/landing/home_fill.svg',
                            'selected_icon_url' => 'https://images.jagods.in/landing/home_fill.svg',

                            'redirection' => [
                                'navigation_type' => 'home',
                            ],
                        ],

                        [
                            'id' => 2,
                            'title' => 'Reels',
                            'slug' => 'Reels',
                            'icon_url' => 'https://images.jagods.in/landing/Reel.svg',
                            'selected_icon_url' => 'https://images.jagods.in/landing/Reel_fill.svg',

                            'redirection' => [
                                'navigation_type' => 'Reels',
                            ],
                        ],

                        [
                            'id' => 3,
                            'title' => 'Cart',
                            'slug' => 'cart',
                            'icon_url' => 'https://images.jagods.in/landing/Cart.svg',
                            'selected_icon_url' => 'https://images.jagods.in/landing/Cart_fill.svg',
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