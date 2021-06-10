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
        $this->app->bind('GameUs', 'App\Services\GameUs');
        $this->app->bind('GameHk', 'App\Services\GameHk');
        $this->app->bind('Translate', 'App\Services\Translate');
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
