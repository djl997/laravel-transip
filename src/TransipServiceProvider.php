<?php

namespace Djl997\LaravelTransip;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;

class TransipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/transip.php', 'transip');

        $this->app->singleton(Transip::class, fn ($app) => new Transip(
            $app->make(Factory::class),
            $app['cache']->store($app['config']['transip.cache_store']),
            $app['config']['transip'],
        ));
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/transip.php' => config_path('transip.php')], 'transip-config');
    }
}
