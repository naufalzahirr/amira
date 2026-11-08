<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Di server, semua tautan (tautan gabung, QR, email lupa sandi) memakai https.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        ResetPassword::toMailUsing(function ($user, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('Atur ulang kata sandi AMIRA')
                ->greeting("Halo, {$user->name}")
                ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun AMIRA-mu.')
                ->action('Atur ulang kata sandi', $url)
                ->line('Tautan ini berlaku 60 menit. Kalau kamu tidak memintanya, abaikan email ini.')
                ->salutation('Salam, AMIRA');
        });
    }
}
