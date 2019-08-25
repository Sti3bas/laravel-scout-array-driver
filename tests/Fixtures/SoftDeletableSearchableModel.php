<?php

namespace Sti3bas\ScoutArray\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class SoftDeletableSearchableModel extends Model
{
    use Searchable, SoftDeletes;

    protected $guarded = [];

    public function searchableAs()
    {
        return 'test_index2';
    }

    public function getScoutKey()
    {
        return $this->scoutKey;
    }
}
