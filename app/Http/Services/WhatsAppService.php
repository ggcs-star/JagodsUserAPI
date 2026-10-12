<?php

namespace App\Http\Services;

use App\Models\CateringBooking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    public function sendNewCateringBooking(
        CateringBooking $booking
    ): bool {
        $token = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $adminNumber = preg_replace(
            '/\D/',
            '',
            (string) config('services.whatsapp.admin_number')
        );

        $version = config('services.whatsapp.graph_version');
        $templateName = config('services.whatsapp.booking_template');
        $language = config('services.whatsapp.template_language', 'en_US');

        if (
            !$token ||
            !$phoneNumberId ||
            !$adminNumber ||
            !$version ||
            !$templateName
        ) {
            Log::warning('WhatsApp configuration is incomplete.', [
                'booking_id' => $booking->id,
            ]);

            return false;
        }

        $eventDate = $booking->event_date
            ? Carbon::parse($booking->event_date)->format('d M Y')
            : 'Not specified';

        $eventTime = $booking->event_time
            ? Carbon::parse($booking->event_time)->format('h:i A')
            : 'Not specified';

        $eventDetails = implode(' ', array_filter([
            $booking->event_type ?: 'Event',
            $eventDate,
            $eventTime,
        ]));

        $parameters = [
            (string) ($booking->customer_name ?: 'Customer'),
            (string) $booking->id,
            $booking->event_date
                ? Carbon::parse($booking->event_date)->format('M d, Y')
                : 'Date not specified',
        ];
        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(10)
                ->post(
                    "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages",
                    [
                        'messaging_product' => 'whatsapp',
                        'to' => $adminNumber,
                        'type' => 'template',

                        'template' => [
                            'name' => $templateName,

                            'language' => [
                                'code' => $language,
                            ],

                            'components' => [
                                [
                                    'type' => 'body',

                                    'parameters' => array_map(
                                        fn($value) => [
                                            'type' => 'text',
                                            'text' => $value,
                                        ],
                                        $parameters
                                    ),
                                ],
                            ],
                        ],
                    ]
                );

            if ($response->failed()) {
                Log::error('WhatsApp booking notification API failed.', [
                    'booking_id' => $booking->id,
                    'http_status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return false;
            }

            Log::info('WhatsApp booking notification accepted by API.', [
                'booking_id' => $booking->id,
                'message_id' => $response->json('messages.0.id'),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('WhatsApp booking notification exception.', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
