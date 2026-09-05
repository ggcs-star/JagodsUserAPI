<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\BackendController;
use App\Traits\ApiResponse;
use App\Http\Requests\Api\StoreCartRequest;
use App\Http\Requests\Api\ApplyCouponRequest;
use App\Http\Requests\Api\UpdateCartRequest;
use App\Http\Services\CartService;
use Illuminate\Http\Request;
use App\Http\Resources\v1\CartResource;
use Illuminate\Validation\ValidationException;

class CartController extends BackendController
{
    use ApiResponse;

    protected $cartService;

    public function __construct(CartService $cartService)
    {
        parent::__construct();
        $this->middleware('auth:api');
        $this->data['site_title'] = 'Backend';
        $this->cartService = $cartService;
    }

    public function index()
    {
        try {

            $cart = $this->cartService->getCart(auth()->id());

            if (!$cart) {
                return $this->successResponse(
                    message: 'Cart is empty.',
                    data: []
                );
            }

            return $this->successResponse(
                message: 'Cart fetched successfully.',
                data: new CartResource($cart)
            );
        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Internal Server Error'
            );
        }
    }

    public function store(StoreCartRequest $request)
    {
        try {

            $cart = $this->cartService->addToCart(
                $request->validated(),
                auth()->id()
            );

            return $this->createdResponse(
                message: 'Item added to cart successfully.',
                data: new CartResource($cart)
            );
        } catch (\Throwable $e) {

            // Module mismatch exception
            if ($e->getCode() == 422) {

                $payload = json_decode($e->getMessage(), true);

                if (json_last_error() === JSON_ERROR_NONE) {

                    return $this->errorResponse(
                        message: $payload['message'],
                        statusCode: 422,
                        data: [
                            'requires_cart_clear' => true,
                            'current_module' => $payload['current_module'],
                            'current_module_name' => $payload['current_module_name'],
                            'new_module' => $payload['new_module'],
                            'new_module_name' => $payload['new_module_name'],
                        ]
                    );
                }
            }

            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }


    public function update(UpdateCartRequest $request)
    {
        try {

            $cart = $this->cartService->updateCartDetails(
                $request->validated(),
                auth()->id()
            );

            return $this->updatedResponse(
                message: 'Cart updated successfully.',
                data: new CartResource($cart)
            );
        } catch (\Throwable $e) {

            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: 400
            );
        }
    }
    public function clear()
    {
        try {

            $this->cartService->clearCart(auth()->id());

            return $this->deletedResponse(
                message: 'Cart cleared successfully.'
            );
        } catch (\Throwable $e) {

            return $this->serverErrorResponse(
                message: config('app.debug')
                    ? $e->getMessage()
                    : 'Internal Server Error'
            );
        }
    }
    public function remove(Request $request)
    {
        try {

            $request->validate([
                'cart_item_id' => 'required|integer|exists:cart_items,id',
            ]);

            $this->cartService->removeItem(
                $request->cart_item_id,
                auth()->id()
            );

            return $this->deletedResponse(
                message: 'Item removed successfully.'
            );
        } catch (ValidationException $e) {

            return $this->notFoundResponse(
                'Cart item not found.'
            );
        } catch (\Throwable $e) {

            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400
            );
        }
    }

    public function quantity(Request $request)
    {
        try {

            $data = $this->cartService->updateQuantity(
                $request->cart_item_id,
                $request->quantity
            );

            return $this->successResponse(
                message: $data['message'] ?? 'Quantity updated successfully.',
                data: $data
            );
        } catch (\Throwable $e) {

            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: 400
            );
        }
    }

    public function applyCoupon(ApplyCouponRequest $request)
    {
        // dd($request->validated());
        try {

            $data = $this->cartService->applyCoupon(
                $request->validated(),
                auth()->id()
            );

            return $this->successResponse(
                message: 'Coupon applied successfully.',
                data: $data
            );
        } catch (\Throwable $e) {

            $statusCode = (int) $e->getCode();

            $statusCode = (
                $statusCode >= 100 &&
                $statusCode <= 599
            ) ? $statusCode : 422;

            return $this->errorResponse(
                message: $e->getMessage(),
                statusCode: $statusCode
            );
        }
    }
}
