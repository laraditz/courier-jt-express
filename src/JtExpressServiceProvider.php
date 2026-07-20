<?php

namespace Laraditz\Courier\JtExpress;

use Illuminate\Support\ServiceProvider;

class JtExpressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/jtexpress.php', 'courier.drivers.jtexpress');
    }

    public function boot(): void
    {
        $this->app->make('courier')->extend(
            'jtexpress',
            fn ($app, $config) => new JtExpressDriver($config)
        );

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/jtexpress.php' => config_path('jtexpress.php'),
            ], 'courier-jt-express-config');
        }
    }
}
