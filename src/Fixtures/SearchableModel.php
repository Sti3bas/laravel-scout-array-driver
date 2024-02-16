<?php

namespace Sti3bas\ScoutArray\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class SearchableModel extends Model
{
    use Searchable;

    protected $guarded = [];

    public function searchableAs()
    {
        return 'test_index';
    }

    public function getScoutKey()
    {
        return $this->scoutKey;
    }
}
