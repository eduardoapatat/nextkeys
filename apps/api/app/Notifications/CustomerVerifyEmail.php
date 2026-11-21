<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * The storefront's email verification link. It opens a Nuxt page, which posts
 * the id, hash and relative signature of the API route
 * `api.v1.auth.verification.verify`.
 */
class CustomerVerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $minutes = Config::integer('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verifica tu correo en NextKeys')
            ->greeting("Hola, {$notifiable->name}")
            ->line('Confirma tu correo electrónico para poder pagar tus compras: ahí te enviaremos las claves.')
            ->action('Verificar correo', $this->storefrontUrl($notifiable, $minutes))
            ->line("El enlace vence en {$minutes} minutos. Si no creaste una cuenta en NextKeys, ignora este correo.");
    }

    private function storefrontUrl(User $notifiable, int $minutes): string
    {
        $parameters = ['id' => $notifiable->id, 'hash' => sha1($notifiable->email)];

        $signed = URL::temporarySignedRoute('api.v1.auth.verification.verify', now()->addMinutes($minutes), $parameters, absolute: false);
        parse_str((string) parse_url($signed, PHP_URL_QUERY), $signature);

        return Config::string('storefront.url').'/cuenta/verificar-email?'.http_build_query([...$parameters, ...$signature]);
    }
}
