<?php

namespace Sti3bas\ScoutArray;

class FakeBuilder
{
    public $index = '*';

    public $wheres = [];

    public $whereIns = [];

    public $whereNotIns = [];

    public $limit;

    public $orders = [];

    public $options = [];

    public $query;

    public function within($index)
    {
        $this->index = $index;

        return $this;
    }

    public function where($field, $value)
    {
        $this->wheres[$field] = $value;

        return $this;
    }

    public function whereIn($field, array $values)
    {
        $this->whereIns[$field] = $values;

        return $this;
    }

    public function whereNotIn($field, array $values)
    {
        $this->whereNotIns[$field] = $values;

        return $this;
    }

    public function withTrashed()
    {
        unset($this->wheres['__soft_deleted']);

        return $this;
    }

    public function onlyTrashed()
    {
        return tap($this->withTrashed(), function () {
            $this->wheres['__soft_deleted'] = 1;
        });
    }

    public function take($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) == 'asc' ? 'asc' : 'desc',
        ];

        return $this;
    }

    public function latest($column = 'created_at')
    {
        return $this->orderBy($column, 'desc');
    }

    public function oldest($column = 'created_at')
    {
        return $this->orderBy($column, 'asc');
    }

    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    public function query($query)
    {
        $this->query = $query;

        return $this;
    }
}
