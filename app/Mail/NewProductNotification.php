<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Fruit;
use App\Models\User;

class NewProductNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $fruit;
    public $user;

    public function __construct(Fruit $fruit, User $user)
    {
        $this->fruit = $fruit;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject("New Product Available: {$this->fruit->name}")
                    ->view('emails.new-product')
                    ->with([
                        'fruit' => $this->fruit,
                        'user' => $this->user
                    ]);
    }
}
