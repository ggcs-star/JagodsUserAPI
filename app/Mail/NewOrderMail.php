<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderLineItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public $items;

    public function __construct(Order $order)
    {
        $this->order = $order;

        $this->items = OrderLineItem::where('order_id', $order->id)
            ->with('menuItem')
            ->get();
    }

    public function build()
    {
        return $this
            ->subject('New Order Received - #' . $this->order->id)
            ->view('emails.new-order');
    }
}