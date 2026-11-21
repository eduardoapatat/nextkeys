<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

/**
 * The storefront's password reset link: a Nuxt page that posts the token to
 * `api.v1.auth.password.update`.
 */
class CustomerResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $minutes = Config::integer('auth.passwords.customers.expire');
        $url = Config::string('storefront.url').'/cuenta/restablecer?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Restablece tu contraseña de NextKeys')
            ->greeting("Hola, {$notifiable->name}")
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("El enlace vence en {$minutes} minutos. Si no lo pediste, ignora este correo: tu contraseña no cambia.");
    }
}
