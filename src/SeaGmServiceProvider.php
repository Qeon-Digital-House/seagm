<?php

namespace Rrq\Seagm;

use Illuminate\Support\ServiceProvider;

class SeaGmServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/seagm.php' => config_path('seagm.php'),
        ], 'seagm-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/seagm.php', 'seagm');

        $this->app->singleton(SeaGm::class, function ($app) {
            return new SeaGm(config('seagm'));
        });

        $this->app->alias(SeaGm::class, 'seagm');
    }
}
