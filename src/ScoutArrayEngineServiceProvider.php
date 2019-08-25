<?php

namespace Sti3bas\ScoutArray;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Sti3bas\ScoutArray\Engines\ArrayEngine;

class ScoutArrayEngineServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(ArrayStore::class, function () {
            return new ArrayStore;
        });

        $this->app[EngineManager::class]->extend('array', function ($app) {
            return new ArrayEngine($this->app[ArrayStore::class], config('scout.soft_delete'));
        });

        $this->app->bind(Search::class, function () {
            return new Search($this->app[ArrayStore::class]);
        });
    }
}
