<?php

namespace App\Jobs;

use App\Models\Order;
use App\Mail\NewOrderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;

    /**
     * Number of attempts
     */
    public int $tries = 3;

    /**
     * Retry delay
     */
    public int $backoff = 30;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        try {

            $order = Order::with([
                'restaurant',
                'user',
            ])->find($this->orderId);

            if (!$order) {
                Log::error('Order email failed: Order not found', [
                    'order_id' => $this->orderId,
                ]);

                return;
            }

            Log::info('Sending new order email', [
                'order_id' => $order->id,
            ]);

            Mail::to(config('mail.order_to'))
                ->cc(config('mail.order_cc'))
                ->send(new NewOrderMail($order));


            Log::info('New order email sent successfully', [
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {

            Log::error('New order email failed', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
