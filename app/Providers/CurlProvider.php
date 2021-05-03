<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App;

class CurlProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('App\ServicesContainer\GameUsService', function(){
            return new App\ServicesContainer\GameUsService();
        });
        App::bind('App\ServicesContainer\TranslateNameService', function() {
            return new App\ServicesContainer\TranslateNameService();
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
