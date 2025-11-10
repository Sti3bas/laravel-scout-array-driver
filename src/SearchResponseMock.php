<?php

namespace Sti3bas\ScoutArray;

use Laravel\Scout\Builder;

class SearchResponseMock
{
    public FakeBuilder $fakeBuilder;

    public array $mockData;

    public function __construct(FakeBuilder $fakeBuilder, array $mockData)
    {
        $this->fakeBuilder = $fakeBuilder;
        $this->mockData = $mockData;
    }

    public function matches(Builder $builder)
    {
        return $builder->wheres == $this->fakeBuilder->wheres &&
            $builder->whereIns == $this->fakeBuilder->whereIns &&
            $builder->whereNotIns == $this->fakeBuilder->whereNotIns &&
            $builder->limit == $this->fakeBuilder->limit &&
            $builder->orders == $this->fakeBuilder->orders &&
            $builder->options == $this->fakeBuilder->options &&
            $builder->query == $this->fakeBuilder->query;
    }

    public function toArray(array $data): array
    {
        return array_merge($data, $this->mockData);
    }
}
