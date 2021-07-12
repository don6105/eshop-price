<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class SummaryProvider extends ServiceProvider implements DeferrableProvider
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
        $this->app->bind('SummaryPrice', 'App\Services\SummaryPrice');
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

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [SummarySync::class, SummaryGroup::class, SummaryPrice::class];
    }
}
