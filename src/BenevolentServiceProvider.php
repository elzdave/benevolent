<?php

namespace Elzdave\Benevolent;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class BenevolentServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        $this->configureProvider();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        config([
            'auth.providers.benevolent' => array_merge([
                'driver' => 'benevolent',
            ], config('auth.providers.benevolent', [])),
        ]);

        $this->mergeConfigFrom(__DIR__.'/../config/benevolent.php', 'benevolent');

        // Register the service the package provides.
        $this->app->singleton('benevolent', function ($app) {
            return new Benevolent($app->make(UserModel::class));
        });
    }

    /**
     * Configure the Benevolent user provider.
     *
     * @return void
     */
    protected function configureProvider()
    {
        Auth::resolved(function ($auth) {
            $auth->provider('benevolent', function($app, array $config) {
                return new Benevolent($app->make(UserModel::class));
            });
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['benevolent'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/benevolent.php' => config_path('benevolent.php'),
        ], 'benevolent.config');
    }
}
