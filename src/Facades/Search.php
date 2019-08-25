<?php

namespace Sti3bas\ScoutArray\Facades;

use Illuminate\Support\Facades\Facade;

class Search extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Sti3bas\ScoutArray\Search::class;
    }
}
