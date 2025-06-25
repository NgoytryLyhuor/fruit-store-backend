<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $user;

    public function __construct($token, User $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    public function build()
    {
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email);

        return $this->subject('Reset Your Password - Pure Flave')
                    ->view('emails.reset-password')
                    ->with([
                        'resetUrl' => $resetUrl,
                        'user' => $this->user,
                        'token' => $this->token
                    ]);
    }
}
