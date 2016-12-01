<?php

namespace Zigo928\Mypackage;

use Illuminate\Support\ServiceProvider;

class MypackageServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/routes/routes.php';
        }

        $this->publishes([__DIR__ . '/config/mypackage.php' => config_path('mypackage.php')]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}