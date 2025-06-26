<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;

class ChangeStatusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;

    public function __construct(Order $order, User $user)
    {
        // Eager load relationships: orderItems and each orderItem's fruit
        $order->load(['orderItems.fruit']);
        $this->order = $order;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject("Order #{$this->order->id} Status Updated - {$this->order->status}")
                    ->view('emails.change-status')
                    ->with([
                        'order' => $this->order,
                        'user' => $this->user
                    ]);
    }
}
