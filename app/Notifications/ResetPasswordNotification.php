<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $expiresInMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Resetează parola contului Rentier')
            ->greeting('Bună!')
            ->line('Ai primit acest mesaj deoarece a fost solicitată resetarea parolei contului tău.')
            ->action('Resetează parola', $this->resetUrl($notifiable))
            ->line("Linkul de resetare expiră în {$expiresInMinutes} de minute.")
            ->line('Dacă nu ai solicitat resetarea parolei, poți ignora acest mesaj.');
    }

    protected function resetUrl($notifiable): string
    {
        $path = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);

        return rtrim((string) config('app.url'), '/').$path;
    }
}
