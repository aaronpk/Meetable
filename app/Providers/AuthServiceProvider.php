<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Services\Auth\VouchGuard;
use App\Services\Auth\GitHubGuard;
use App\Services\Auth\DiscordGuard;
use App\Services\Auth\HerokuGuard;
use App\Services\Auth\OIDCGuard;
use Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::extend('vouch', function($app, $name, array $config) {
            return new VouchGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });

        Auth::extend('github', function($app, $name, array $config) {
            return new GitHubGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });

        Auth::extend('heroku', function($app, $name, array $config) {
            return new HerokuGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });

        Auth::extend('discord', function($app, $name, array $config) {
            return new DiscordGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });

        Auth::extend('oidc', function($app, $name, array $config) {
            return new OIDCGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });

        Gate::define('create-event', function($user) {
            if(!$user)
                return false;

            // Check if the site is configured for any logged-in user to add events or just admins
            if(env('ALLOW_MANAGE_EVENTS') == 'users')
                return true;

            if(env('ALLOW_MANAGE_EVENTS') == 'admins')
                return $user->is_admin == 1;

            return false;
        });

        Gate::define('manage-event', function($user, $event) {
            if(!$user)
                return false;

            // Check if the site is configured for any logged-in user to add events or just admins.
            // Currently all users can manage all events. Later can add an option to limit
            // users to be able to edit only their own events
            if(env('ALLOW_MANAGE_EVENTS') == 'users')
                return true;

            if(env('ALLOW_MANAGE_EVENTS') == 'admins')
                return $user->is_admin == 1;

            return false;
        });

        Gate::define('manage-site', function($user) {
            if(!$user)
                return false;

            if(env('ALLOW_MANAGE_SITE') == 'users')
                return true;

            if(env('ALLOW_MANAGE_SITE') == 'admins')
                return $user->is_admin == 1;

            return false;
        });
    }
}
