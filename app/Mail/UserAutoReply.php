<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $messageData) {}

    public function build()
    {
        return $this->subject('¡Gracias por escribir a Shift+IA!')
            ->view('emails.user_auto_reply');
    }
}
