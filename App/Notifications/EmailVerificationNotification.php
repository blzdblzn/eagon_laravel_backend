<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\EmailVerificationNotification;
use Otp;

class EmailVerificationNotification extends Notification
{
    use Queueable;
    public $message;
    public $subject;
    public $fromEmail;
    public $realtoken;
    public $mailer;
    private $otp;
    public $generatedOtp;
    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->message = 'use the below code for verification process';
        $this->subject = 'Eagon verification ';
        $this->fromEmail = 'abbeyisblessed@gmail.com';
        $this->mailer = 'smtp';
        $this->otp = new Otp;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
{
    $this->generatedOtp = $this->otp->generate($notifiable->email, 'numeric', 6);
    $mailMessage = (new MailMessage)
        ->mailer('smtp')
        ->subject($this->subject)
        ->greeting('Hello ' . $notifiable->name)
        ->line($this->message);

    // Include the OTP in the mail message
    $realtoken = $this->generatedOtp->token;
    $mailMessage->line('code: ' . $realtoken);
    
    return $mailMessage;
}
        /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            $mailMessage, $realtoken
        ];
    }
   
}
