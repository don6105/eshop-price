<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CrawlProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Game', function($app, $args) {
            $country    = $args['country']?? '';
            $class_name = '\\App\\Services\\Game'.ucfirst($country);
            return class_exists($class_name)? new $class_name() : null;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
