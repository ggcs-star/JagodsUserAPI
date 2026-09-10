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

                    'icon_url' => 'https://images.jagods.in/landing/Near%20You.svg',

                    'promo_banner' => [
                        'type' => 'gif',
                        'image_url' => 'https://images.jagods.in/landing/Near%20You.gif',

                        'action' => [
                            'type' => 'navigation',
                            'navigation_type' => 'category',
                            'value' => 'fresh-vegetables',

                            'params' => [
                                'category_id' => 15,
                            ],
                        ],
                    ],
                    'promo_banners' => [
                        [
                            'id' => 1,
                            'image_url' => 'https://images.jagods.in/landing/jagods-banner-1.svg',
                        ],
                        [
                            'id' => 2,
                            'image_url' => 'https://images.jagods.in/landing/jagods-banner-2.svg',
                        ],
                        [
                            'id' => 3,
                            'image_url' => 'https://images.jagods.in/landing/jagods-banner-3.svg',
                        ],
                        [
                            'id' => 4,
                            'image_url' => 'https://images.jagods.in/landing/jagods-banner-4.svg',
                        ],
                        [
                            'id' => 5,
                            'image_url' => 'https://images.jagods.in/landing/jagods-banner-5.svg',
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
                        'border_color' => '#427C6D',
                        'background_color' => '#427C6D',
                    ],

                    'search_placeholder' => [
                        'Search for products, "Vadapav"',
                        'Search for products, "Cold Coffee"',
                        'Search for products, "Sandwich"',
                        'Search for products, "Biryani"',
                        'Search for products, "Dosa"',
                        'Search for products, "Chole Bhature"',
                        'Search for products, "Pav Bhaji"',
                    ],


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
                        'text' => 'India Wide',
                        'icon_url' => 'https://images.jagods.in/landing/All%20Over%20India.svg',
                        'background_color' => '#D32F2F',
                        'text_color' => '#FFFFFF',
                        'border_color' => '#B71C1C',

                        'gradient' => [
                            '#EF5350',
                            '#D32F2F',
                        ],
                    ],

                    'icon_url' => 'https://images.jagods.in/landing/PAN%20India.svg',

                    'promo_banner' => [
                        'type' => 'gif',
                        'image_url' => 'https://images.jagods.in/landing/Pan%20India.gif',

                        'action' => [
                            'type' => 'navigation',
                            'navigation_type' => 'category',
                            'value' => 'daily-grocery',

                            'params' => [
                                'category_id' => 25,
                            ],
                        ],
                    ],
                    'promo_banners' => [
                        [
                            'id' => 1,
                            'image_url' => 'https://images.jagods.in/landing/images%20(5).jpg',
                        ],
                        [
                            'id' => 2,
                            'image_url' => 'https://images.jagods.in/landing/images%20(6).jpg',
                        ],
                        [
                            'id' => 3,
                            'image_url' => 'https://images.jagods.in/landing/images%20(7).jpg',
                        ],
                        [
                            'id' => 4,
                            'image_url' => 'https://images.jagods.in/landing/images%20(8).jpg',
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
                        'border_color' => '#7AA2C5',
                        'background_color' => '#7AA2C5',
                    ],

                    'search_placeholder' => [
                        'Search for products, "Kaju Katli"',
                        'Search for products, "Ajwa Khajur"',
                        'Search for products, "Sohan Papdi"',
                        'Search for products, "Laddu"',
                        'Search for products, "Gathiya"',
                        'Search for products, "Chakri"',
                        'Search for products, "Mukhwas"',
                        'Search for products, "Juice"',
                        'Search for products, "Sev"',
                        'Search for products, "Bhujia"',
                        'Search for products, "Farsan"',
                        'Search for products, "Gulab Jamun"',
                        'Search for products, "Mohanthal"',
                        'Search for products, "Kaju Pista Roll"',
                        'Search for products, "Dry Fruits"',
                    ],



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
