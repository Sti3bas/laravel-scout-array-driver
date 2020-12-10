<?php

namespace Sti3bas\ScoutArray\Tests\Fixtures;

use DateTimeInterface;
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

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
