<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminNewMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $messageData) {}

    public function build()
    {
        return $this->subject('Nuevo contacto en Shift+IA')
            ->view('emails.admin_new_message');
    }
}
