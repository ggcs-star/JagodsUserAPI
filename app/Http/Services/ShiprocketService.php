<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ShiprocketService
{
    private string $baseUrl = 'https://apiv2.shiprocket.in/v1/external';

    private function getToken(): string
    {
        return Cache::remember(
            'shiprocket_api_token',
            now()->addHours(23),
            function () {

                $response = Http::acceptJson()
                    ->post(
                        $this->baseUrl . '/auth/login',
                        [
                            'email' => config('services.shiprocket.email'),
                            'password' => config('services.shiprocket.password'),
                        ]
                    );

                if (!$response->successful()) {

                    Log::error('Shiprocket Authentication Failed', [
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);

                    throw new RuntimeException(
                        'Shiprocket authentication failed.'
                    );
                }

                $token = $response->json('token');

                if (blank($token)) {
                    throw new RuntimeException(
                        'Shiprocket token not received.'
                    );
                }

                return $token;
            }
        );
    }

    public function getDeliveryRate(
        string $deliveryPincode,
        float $weight,
        int $cod = 0
    ): array {


        if (!config('services.shiprocket.enabled')) {
            return $this->getStaticDeliveryRate();
        }

        $pickupPincode = config(
            'services.shiprocket.pickup_postcode'
        );

        if (blank($pickupPincode)) {
            throw new RuntimeException(
                'Shiprocket pickup postcode is not configured.'
            );
        }

        $token = $this->getToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->get(
                $this->baseUrl . '/courier/serviceability/',
                [
                    'pickup_postcode' => $pickupPincode,
                    'delivery_postcode' => $deliveryPincode,
                    'cod' => $cod,
                    'weight' => $weight,
                ]
            );


        if ($response->status() === 401) {

            Cache::forget('shiprocket_api_token');

            $token = $this->getToken();

            $response = Http::withToken($token)
                ->acceptJson()
                ->get(
                    $this->baseUrl . '/courier/serviceability/',
                    [
                        'pickup_postcode' => $pickupPincode,
                        'delivery_postcode' => $deliveryPincode,
                        'cod' => $cod,
                        'weight' => $weight,
                    ]
                );
        }


        if (!$response->successful()) {

            Log::error('Shiprocket Serviceability Failed', [
                'pickup_postcode' => $pickupPincode,
                'delivery_postcode' => $deliveryPincode,
                'weight' => $weight,
                'cod' => $cod,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new RuntimeException(
                'Unable to fetch delivery serviceability.'
            );
        }

        $couriers = data_get(
            $response->json(),
            'data.available_courier_companies',
            []
        );

        if (empty($couriers)) {

            return [
                'available' => false,
                'message' => 'Delivery is not available for this pincode.',
                'courier' => null,
            ];
        }


        usort($couriers, function ($a, $b) {

            return (float) data_get($a, 'freight_charge', 0)
                <=> (float) data_get($b, 'freight_charge', 0);
        });

        $courier = $couriers[0];

        $deliveryCharge = (float) data_get(
            $courier,
            'freight_charge',
            0
        );

        $codCharge = (float) data_get(
            $courier,
            'cod_charges',
            0
        );

        $totalCharge = $deliveryCharge + $codCharge;

        return [
            'available' => true,
            'message' => 'Delivery is available for this pincode.',
            'courier' => [
                'courier_id' => data_get(
                    $courier,
                    'courier_company_id'
                ),

                'courier_name' => data_get(
                    $courier,
                    'courier_name'
                ),

                'delivery_charge' => round($deliveryCharge, 2),

                'cod_charge' => round($codCharge, 2),

                'total_charge' => round($totalCharge, 2),

                'estimated_delivery_days' => data_get(
                    $courier,
                    'etd'
                ),

                'rating' => data_get(
                    $courier,
                    'rating'
                ),
            ],
        ];
    }


    private function getStaticDeliveryRate(): array
    {
        return [
            'available' => true,
            'message' => 'Delivery is available for this pincode.',
            'courier' => [
                'courier_id' => 1,
                'courier_name' => 'Blue Dart Air demo',
                'delivery_charge' => 879.90,
                'cod_charge' => 55.65,
                'total_charge' => 935.55,
                'estimated_delivery_days' => 'Aug 23, 2026',
                'rating' => 3,
            ],
        ];
    }
}