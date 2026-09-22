<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Http\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderCreatedNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $orderId,
        public int $userId
    ) {}

    public function handle(
        PushNotificationService $pushNotificationService
    ): void {

        $order = Order::find($this->orderId);

        if (!$order) {
            Log::warning(
                'Order created notification skipped: order not found.',
                [
                    'order_id' => $this->orderId,
                ]
            );

            return;
        }

        $user = User::find($this->userId);

        if (!$user) {
            Log::warning(
                'Order created notification skipped: user not found.',
                [
                    'order_id' => $this->orderId,
                    'user_id' => $this->userId,
                ]
            );

            return;
        }

        if (
            (int) $order->payment_status !==
            \App\Enums\PaymentStatus::PAID
        ) {
            Log::warning(
                'Order created notification skipped: payment not completed.',
                [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                ]
            );

            return;
        }

        $pushNotificationService->NotificationForCustomer(
            $order,
            $user,
            'order_created'
        );

        Log::info(
            'Customer order created notification sent.',
            [
                'order_id' => $order->id,
                'user_id' => $user->id,
            ]
        );


        try {

            $adminApiUrl = config('services.admin_api.url');
            $adminApiToken = config('services.admin_api.token');

            if (
                empty($adminApiUrl) ||
                empty($adminApiToken)
            ) {
                Log::warning(
                    'Admin order notification skipped: admin API configuration missing.',
                    [
                        'order_id' => $order->id,
                    ]
                );
            } else {

                $adminResponse = Http::timeout(10)
                    ->acceptJson()
                    ->withToken($adminApiToken)
                    ->post(
                        rtrim($adminApiUrl, '/') .
                            '/api/internal/order-created',
                        [
                            'order_id' => $order->id,

                            'restaurant_id' => $order->restaurant_id,

                            'user_id' => $order->user_id,

                            'order_code' => $order->order_code ?? null,

                            'amount' => $order->total,
                        ]
                    );

                if (!$adminResponse->successful()) {

                    Log::error(
                        'Admin order notification API failed.',
                        [
                            'order_id' => $order->id,
                            'status' => $adminResponse->status(),
                            'response' => $adminResponse->body(),
                        ]
                    );
                } else {

                    Log::info(
                        'Admin order notification sent successfully.',
                        [
                            'order_id' => $order->id,
                            'admin_response' => $adminResponse->json(),
                        ]
                    );
                }
            }
        } catch (Throwable $exception) {

            Log::error(
                'Admin order notification API exception.',
                [
                    'order_id' => $order->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error(
            'Order created notification job failed permanently.',
            [
                'order_id' => $this->orderId,
                'user_id' => $this->userId,
                'error' => $exception->getMessage(),
            ]
        );
    }
}
