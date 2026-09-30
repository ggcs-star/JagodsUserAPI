<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BackendController;
use App\Traits\ApiResponse;

class CateringConfigController extends BackendController
{
    use ApiResponse;

    public function index()
    {
        $data = [

            'hero' => [
                'title' => 'Make Your Event Special',

                'subtitle' => 'With Our Catering Packages',

                'image_url' => 'https://images.jagods.in/catering/catering-hero.jpg',

                'action' => [
                    'type' => 'navigation',
                    'navigation_type' => 'catering_packages',
                    'value' => 'catering-packages',

                    'params' => [
                        'module_id' => 3,
                    ],
                ],
            ],

            'categories' => [

                [
                    'id' => 1,
                    'name' => 'Wedding Catering',
                    'slug' => 'wedding-catering',

                    'icon_url' => 'https://images.jagods.in/catering/icons/wedding.svg',

                    'background_color' => '#FFF7E8',
                    'icon_color' => '#F59E0B',

                    'action' => [
                        'type' => 'navigation',
                        'navigation_type' => 'catering_category',
                        'value' => 'wedding-catering',

                        'params' => [
                            'category' => 'wedding',
                        ],
                    ],
                ],

                [
                    'id' => 2,
                    'name' => 'Corporate Catering',
                    'slug' => 'corporate-catering',

                    'icon_url' => 'https://images.jagods.in/catering/icons/corporate.svg',

                    'background_color' => '#ECFDF3',
                    'icon_color' => '#16A34A',

                    'action' => [
                        'type' => 'navigation',
                        'navigation_type' => 'catering_category',
                        'value' => 'corporate-catering',

                        'params' => [
                            'category' => 'corporate',
                        ],
                    ],
                ],

                [
                    'id' => 3,
                    'name' => 'Birthday Catering',
                    'slug' => 'birthday-catering',

                    'icon_url' => 'https://images.jagods.in/catering/icons/birthday.svg',

                    'background_color' => '#FFF1F2',
                    'icon_color' => '#EF4444',

                    'action' => [
                        'type' => 'navigation',
                        'navigation_type' => 'catering_category',
                        'value' => 'birthday-catering',

                        'params' => [
                            'category' => 'birthday',
                        ],
                    ],
                ],

                [
                    'id' => 4,
                    'name' => 'Party Catering',
                    'slug' => 'party-catering',

                    'icon_url' => 'https://images.jagods.in/catering/icons/party.svg',

                    'background_color' => '#F5F3FF',
                    'icon_color' => '#7C3AED',

                    'action' => [
                        'type' => 'navigation',
                        'navigation_type' => 'catering_category',
                        'value' => 'party-catering',

                        'params' => [
                            'category' => 'party',
                        ],
                    ],
                ],

            ],


        ];

        return $this->successResponse(
            message: 'Catering configuration retrieved successfully.',
            data: $data
        );
    }
}
