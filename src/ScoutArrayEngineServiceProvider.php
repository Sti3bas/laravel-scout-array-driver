<?php

namespace Sti3bas\ScoutArray;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Sti3bas\ScoutArray\Engines\ArrayEngine;

class ScoutArrayEngineServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ArrayStore::class, function () {
            return new ArrayStore;
        });

        $this->app->bind(Search::class, function () {
            return new Search($this->app[ArrayStore::class]);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(EngineManager $engineManager)
    {
        $engineManager->extend('array', function () {
            return new ArrayEngine($this->app[ArrayStore::class], config('scout.soft_delete'));
        });
    }
}
