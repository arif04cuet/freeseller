<?php

namespace App\Notifications;

use App\Enum\SystemRole;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class AccountActivationNotification extends Notification
{
    use Queueable;

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
        $role = $notifiable->roles->first()->name;
        $whatsAppLink = 'https://chat.whatsapp.com/J62Or38RDfgHLSAU05n01k';
        $youTubeLink = 'https://www.youtube.com/@FreeSeller-iu3fe';
        $minimum = config('freeseller.minimum_acount_balance');

        return (new MailMessage)
            ->subject('Your account has been activated!')
            ->line('Hello, ' . $notifiable->name)
            ->line('Thanks for creating a ' . $role . ' account at FreeSeller')
            ->lineIf($role == SystemRole::Reseller->value, new HtmlString('<b>Start your reselling in 3 steps.</b>'))
            ->lineIf($role == SystemRole::Reseller->value, new HtmlString('1. Join our WhatsApp reseller group via  <a href="' . $whatsAppLink . '">WhatsApp</a>'))
            ->lineIf($role == SystemRole::Reseller->value, new HtmlString('2. Read our FAQ. <a href="' . url('/') . '">FAQ</a>'))
            ->lineIf($role == SystemRole::Reseller->value, '3. To make order, recharge your wallet with minimum TK. ' . $minimum)
            ->line('You can login now.')
            ->action('Login', filament()->getLoginUrl())
            ->line('Thank you');
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
