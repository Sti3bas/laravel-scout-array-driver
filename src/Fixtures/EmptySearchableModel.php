<?php

namespace Sti3bas\ScoutArray\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class EmptySearchableModel extends Model
{
    use Searchable;

    protected $guarded = [];

    public function searchableAs()
    {
        return 'test_index3';
    }

    public function getScoutKey()
    {
        return $this->scoutKey;
    }

    public function toSearchableArray()
    {
        return [];
    }
}
