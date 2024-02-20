<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class welcomemail extends Mailable
{
    use Queueable, SerializesModels;
   public $get_user_email;
   public $validToken;
   public $get_user_name;
    

    /**
     * Create a new message instance.
     */
    public function __construct($get_user_email, $validToken, $get_user_name)
    {
        $this->$get_user_email = $get_user_email;
        $this->$validToken = $validToken;
        $this->$get_user_name = $get_user_name;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcomemail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
