<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserLockAccount extends Notification
{
    use Queueable;


    private $name;

    /**
     * Create a new notification instance.
     */
    public function __construct($name)
    {
        $this->name = $name;
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
        $support_mail = config('custom.support_email');
        return (new MailMessage)
        ->subject('Important Notice: Your Account Has Been Temporarily Locked')
        ->greeting("Dear {$this->name}")
        ->line("We regret to inform you that your account has been temporarily locked due to multiple unsuccessful login attempts. This measure is taken to ensure the security of your account and to protect your personal information.")
        ->line("The account lock is temporary and will automatically be lifted after 24 hours from the time of the lock. During this period, you will not be able to access your account.")
        ->line('We recommend that you review your login details and ensure that you are using the correct password. If you have forgotten your password, please use the "Forgot Password" feature on our website to reset it.')
        ->line("If you have any questions or need further information, please do not hesitate to contact us at to {$support_mail}.")
        ->line('Thank you for your attention to this matter.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
