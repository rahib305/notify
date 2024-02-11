<?php

namespace ByteBlitz\Notify;

use ByteBlitz\Notify\Notify;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class NotifyServiceProvider extends ServiceProvider {


    public function boot() {
        $this->registerBladeDirective();
        $this->registerPublishables();
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'notify');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }


    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../config/notify.php', 'notify');

        $this->app->singleton('notify', function ($app) {
            return new Notify();
        });

    }

    public function registerBladeDirective(): void
    {
        Blade::directive('emailCss', function () {
            return '<?php echo emailCss(); ?>';
        });
    }


    public function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../config/notify.php' => config_path('notify.php'),
        ], 'notify-config');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/byteblitz/notify'),
        ], 'notify-assets');
    }


}
