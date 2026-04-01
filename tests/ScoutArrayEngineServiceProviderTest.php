<?php

namespace Sti3bas\ScoutArray\Tests;

use Laravel\Scout\EngineManager;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase;
use Sti3bas\ScoutArray\Engines\ArrayEngine;
use Sti3bas\ScoutArray\ScoutArrayEngineServiceProvider;

class ScoutArrayEngineServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class,
            ScoutArrayEngineServiceProvider::class,
        ];
    }

    public function test_it_can_resolve_the_array_engine_from_the_engine_manager()
    {
        $engine = $this->app[EngineManager::class]->engine('array');

        $this->assertInstanceOf(ArrayEngine::class, $engine);
    }
}
