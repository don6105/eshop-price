<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App;

class CrawlProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('GameUs',    'App\Services\GameUs');
        App::bind('Translate', 'App\Services\Translate');
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
