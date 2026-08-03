<?php
/**
 * Created by PhpStorm.
 * User: dipok
 * Date: 19/4/20
 * Time: 4:10 PM
 */

namespace App\Http\Resources\v1;

use App\Models\Order;
use Illuminate\Http\Resources\Json\JsonResource;

class MeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'email'             => $this->email,
            'username'          => $this->username,
            'phone'             => $this->phone,
            'address'           => $this->address,
            'name'              => $this->first_name . ' ' . $this->last_name,
            'status'            => (int)$this->status,
            'applied'           => (int)$this->applied,
            
            // Fix 1: Changed auth()->user()->myrole to $this->myrole
            'totalOrders'       => $this->myrole == 4 ? $this->orderCount() : $this->orders()->count(),
            
            'totalReservations' => $this->reservations()->count(),
            'image'             => $this->image,
            'myrole'            => $this->getrole->name ?? '',
            "balance"           => isset($this->balance->balance) ? currencyFormat($this->balance->balance) : '₹0.00',
            'deposit_amount'    => isset($this->deposit->deposit_amount) ? currencyFormat($this->deposit->deposit_amount) : '',
            'limit_amount'      => isset($this->deposit->limit_amount) ? currencyFormat($this->deposit->limit_amount) : '',
            'mystatus'          => $this->mystatus,
            'restaurant'        => !blank($this->restaurant) ? new RestaurantResource($this->restaurant) : [],
        ];
    }

    private function orderCount(){
        // Fix 2: Changed auth()->user()->id to $this->id
        $orders = Order::where(['delivery_boy_id' => $this->id])->get();
        return $orders->count();
    }
}