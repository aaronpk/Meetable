<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Setting;
use Config;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if($email = Setting::value('mail_from_address')) {
            Config::set('mail', [
                'driver' => 'mailgun',
                'from' => [
                    'address' => $email,
                    'name' => env('APP_NAME'),
                ],
                'mailgun' => [
                    'domain' => Setting::value('mailgun_domain'),
                    'secret' => Setting::value('mailgun_secret'),
                ]
            ]);
        }
    }
}
