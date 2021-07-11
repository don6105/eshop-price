<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class WikiGameProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('WikiGame', 'App\Services\WikiGame');
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
