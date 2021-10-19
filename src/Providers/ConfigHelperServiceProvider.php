<?php

namespace AnourValar\ConfigHelper\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigHelperServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // config
        $this->mergeConfigFrom(__DIR__.'/../resources/config/config_helper.php', 'config_helper');
        $this->publishes([ __DIR__.'/../resources/config/config_helper.php' => config_path('config_helper.php')], 'config');
    }
}
