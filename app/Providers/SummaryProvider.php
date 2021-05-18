<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SummaryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Summary', 'App\Services\Summary');
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
