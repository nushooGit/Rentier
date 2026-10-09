<?php

namespace App\Notifications\Renters;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RenterPortalInvitation extends Notification
{
    public function __construct(private readonly string $token)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Rentier renter portal invitation'))
            ->line(__('You have been invited to access your rental information in Rentier.'))
            ->line(__('Sign in using the invited email address and verify it before accepting.'))
            ->action(__('Review invitation'), route('login', ['renter_invitation' => $this->token]));
    }
}
