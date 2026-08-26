<?php

namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\BackendController;
use App\Traits\ApiResponse;
use App\Http\Requests\Api\CheckoutRequest;
use App\Http\Services\CheckoutServiceNew;
use App\Http\Services\PaymentServiceNew;
use App\Enums\PaymentMethod;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\VerifyPaymentRequest;
use App\Http\Requests\Api\RepayOrderRequest;
use App\Models\Order;
use App\Jobs\SendOrderInvoiceJob;
use App\Jobs\SendOrderCreatedNotificationJob;
class CheckoutController extends BackendController
{
    use ApiResponse;

    protected $checkoutService;
    protected $paymentService;

    public function __construct(CheckoutServiceNew $checkoutService, PaymentServiceNew $paymentService)
    {
        parent::__construct();
        $this->middleware('auth:api');
        $this->checkoutService = $checkoutService;
        $this->paymentService = $paymentService;
    }

    public function checkout(CheckoutRequest $request)
    {
        $order = null;

        try {
            $currentDevice = $request->attributes->get('current_device');

            $order = $this->checkoutService->checkout($request->validated(), auth()->id(), $currentDevice);

            if ((int) $request->payment_method === PaymentMethod::CASH_ON_DELIVERY) {
                return $this->successResponse(
                    message: 'Order placed successfully.',
                    data: $order
                );
            }

            $payment = $this->paymentService->create($order);

            return $this->successResponse(
                message: 'Payment initialized successfully.',
                data: [
                    'order' => $order,
                    'payment' => $payment,
                ]
            );

        } catch (Exception $e) {
            if ($order && (int) $request->payment_method !== PaymentMethod::CASH_ON_DELIVERY) {
                $order->orderLines()->delete();
                $order->delete();
                Log::error("Payment Error: Order ID {$order->id} deleted. Exception: " . $e->getMessage());
            }

            $statusCode = (int) $e->getCode();
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 422;

            $payload = json_decode($e->getMessage(), true);
            if (json_last_error() === JSON_ERROR_NONE && isset($payload['error_type'])) {
                return $this->errorResponsecart(
                    message: $payload['message'],
                    statusCode: $statusCode,
                    data: $payload
                );
            }

            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function verifyPayment(VerifyPaymentRequest $request)
    {
        try {

            $order = Order::find($request->order_id);

            if (!$order) {
                return $this->errorResponse(
                    'Order not found',
                    404
                );
            }

            if ((int) $order->user_id !== (int) auth()->id()) {
                return $this->errorResponse(
                    'You are not authorized to verify this order',
                    403
                );
            }

            $this->paymentService->verify(
                $order,
                $request->validated()
            );

            $order->update([
                'payment_status' => \App\Enums\PaymentStatus::PAID,
                'status' => \App\Enums\OrderStatus::PENDING,
            ]);

            $order->refresh();

            SendOrderInvoiceJob::dispatch($order);
            SendOrderCreatedNotificationJob::dispatch(
                orderId: $order->id,
                userId: auth()->id()
            );
            return $this->successResponse(
                message: 'Payment verified successfully.',
                data: [

                    'title' => 'Congratulations!',

                    'message' => 'Your order has been successfully placed.',

                    'total_amount' => (float) $order->total,

                    'payment_status' => [
                        'code' => (int) $order->payment_status,
                        'name' => 'Successful',
                    ],
                    'module_id' => (int) $order->module_id,
                    'order_id' => $order->id,

                    'order_code' => data_get(
                        json_decode($order->misc, true),
                        'order_code'
                    ),

                    'order_date' => optional(
                        $order->created_at
                    )->format('d M Y, h:i A'),
                ]
            );

        } catch (Exception $e) {

            $statusCode = (int) $e->getCode();

            $statusCode = (
                $statusCode >= 100 &&
                $statusCode <= 599
            )
                ? $statusCode
                : 400;

            return $this->errorResponse(
                $e->getMessage(),
                $statusCode
            );
        }
    }

    public function repayOrder(RepayOrderRequest $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return $this->notFoundResponse('Order not found.');
        }
        try {
            $order = Order::where('id', $request->order_id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$order) {
                return $this->errorResponse('This order does not belong to you or has been removed.', 404);
            }

            if ($order->payment_status == \App\Enums\PaymentStatus::PAID) {
                return $this->errorResponse('This order is already paid.', 400);
            }

            if ($order->status == \App\Enums\OrderStatus::CANCEL) {
                return $this->errorResponse('Cancelled orders cannot be paid.', 400);
            }

            $paymentData = $this->paymentService->create($order);

            return $this->successResponse(
                message: 'Repayment initiated successfully.',
                data: $paymentData
            );

        } catch (Exception $e) {
            $statusCode = (int) $e->getCode();
            $statusCode = ($statusCode >= 100 && $statusCode <= 599) ? $statusCode : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
}