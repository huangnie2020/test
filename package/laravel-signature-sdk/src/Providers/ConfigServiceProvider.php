<?php

namespace Nick\Signature\Api\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{

    const CONFIG_URI = '/../../config/app-sign.php';

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . self::CONFIG_URI => config_path('app-sign.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . self::CONFIG_URI,
            'app-sign'
        );
    }
}
