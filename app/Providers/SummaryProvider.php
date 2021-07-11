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
        $this->app->bind('SummarySync',  'App\Services\SummarySync');
        $this->app->bind('SummaryGroup', 'App\Services\SummaryGroup');
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
