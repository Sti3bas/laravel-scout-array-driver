<?php

namespace Sti3bas\ScoutArray;

use Closure;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Assert;
use Sti3bas\ScoutArray\Engines\ArrayEngine;

class Search
{
    protected ArrayStore $store;

    protected ArrayEngine $engine;

    public function __construct(ArrayEngine $engine)
    {
        $this->store = $engine->store;
        $this->engine = $engine;
    }

    public function assertContains(Model $model, ?Closure $callback = null): self
    {
        Assert::assertCount(
            1,
            $this->store->find($model->searchableAs(), function ($record) use ($model, $callback) {
                return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model exists in '{$model->searchableAs()}' search index."
        );

        return $this;
    }

    public function assertNotContains(Model $model, ?Closure $callback = null): self
    {
        Assert::assertFalse(
            count($this->store->find($model->searchableAs(), function ($record) use ($model, $callback) {
                return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
            })) === 1,
            "Failed asserting that model doesn't exist in '{$model->searchableAs()}' search index."
        );

        return $this;
    }

    public function assertContainsIn(string $index, Model $model, ?Closure $callback = null): self
    {
        Assert::assertCount(
            1,
            $this->store->find($index, function ($record) use ($model, $callback) {
                return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model exists in '{$index}' search index."
        );

        return $this;
    }

    public function assertNotContainsIn(string $index, Model $model, ?Closure $callback = null): self
    {
        Assert::assertFalse(
            count($this->store->find($index, function ($record) use ($model, $callback) {
                return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
            })) === 1,
            "Failed asserting that model doesn't exist in '{$index}' search index."
        );

        return $this;
    }

    public function assertEmpty(): self
    {
        Assert::assertEquals(0, $this->store->count(), 'Failed asserting that all search indexes are empty.');

        return $this;
    }

    public function assertEmptyIn(string $index): self
    {
        Assert::assertEquals(
            0,
            $this->store->count($index),
            "Failed asserting that '{$index}' search index is empty."
        );

        return $this;
    }

    public function assertNotEmpty(): self
    {
        Assert::assertTrue(
            $this->store->count() > 0,
            'Failed asserting that search index is not empty.'
        );

        return $this;
    }

    public function assertNotEmptyIn(string $index): self
    {
        Assert::assertTrue(
            $this->store->count($index) > 0,
            "Failed asserting that '{$index}' search index is not empty."
        );

        return $this;
    }

    public function assertCount(int $count, ?Closure $callback = null): self
    {
        $countFiltered = count($this->store->find($this->store->getDefaultIndex(), function ($record) use ($callback) {
            return ($callback ? $callback($record) : true);
        }));

        Assert::assertSame(
            $countFiltered, $count, 'Failed asserting that search index does not have the expected size.'
        );

        return $this;
    }

    public function assertCountIn(string $index, int $count, ?Closure $callback = null): self
    {
        $countFiltered = count($this->store->find($index, function ($record) use ($callback) {
            return ($callback ? $callback($record) : true);
        }));

        Assert::assertSame(
            $countFiltered, $count, "Failed asserting that '{$index}' search index does not have the expected size."
        );

        return $this;
    }

    public function assertSynced(Model $model, ?Closure $callback = null): self
    {
        Assert::assertNotEmpty(
            $this->store->findInHistory($model->searchableAs(), function ($record) use ($model, $callback) {
                return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model was synced to '{$model->searchableAs()}' search index."
        );

        return $this;
    }

    public function assertNotSynced(Model $model, ?Closure $callback = null): self
    {
        Assert::assertCount(
            0,
            $this->store->findInHistory($model->searchableAs(), function ($record) use ($model, $callback) {
                return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model was not synced to '{$model->searchableAs()}' search index."
        );

        return $this;
    }

    public function assertSyncedTo(string $index, Model $model, ?Closure $callback = null): self
    {
        Assert::assertNotEmpty(
            $this->store->findInHistory($index, function ($record) use ($model, $callback) {
                return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model was synced to '{$index}' search index."
        );

        return $this;
    }

    public function assertNotSyncedTo(string $index, Model $model, ?Closure $callback = null): self
    {
        Assert::assertEmpty(
            $this->store->findInHistory($index, function ($record) use ($model, $callback) {
                return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model was not synced to '{$index}' search index."
        );

        return $this;
    }

    public function assertSyncedTimes(Model $model, int $times, ?Closure $callback = null): self
    {
        $syncedTimes = count($this->store->findInHistory($model->searchableAs(), function ($record) use ($model, $callback) {
            return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
        }));

        Assert::assertTrue(
            $syncedTimes === $times,
            "Failed asserting that model was synced to '{$model->searchableAs()}' search index {$times} times. It was synced {$syncedTimes} instead."
        );

        return $this;
    }

    public function assertSyncedTimesTo(string $index, Model $model, int $times, ?Closure $callback = null): self
    {
        $syncedTimes = count($this->store->findInHistory($index, function ($record) use ($model, $callback) {
            return $record['objectID'] === (string) $model->getScoutKey() && ($callback ? $callback($record) : true);
        }));

        Assert::assertTrue(
            $syncedTimes === $times,
            "Failed asserting that model was synced to '{$index}' search index {$times} times. It was synced {$syncedTimes} instead."
        );

        return $this;
    }

    public function assertNothingSynced(): self
    {
        Assert::assertEquals(
            0,
            $this->store->countInHistory(),
            'Failed asserting that nothing was synced to search index.'
        );

        return $this;
    }

    public function assertNothingSyncedTo(string $index): self
    {
        Assert::assertEquals(
            0,
            $this->store->countInHistory($index),
            "Failed asserting that nothing was synced to '{$index}' search index."
        );

        return $this;
    }

    public function assertIndexExists($index)
    {
        Assert::assertTrue(
            $this->store->indexExists($index),
            "Failed asserting that '{$index}' search index exists."
        );

        return $this;
    }

    public function assertIndexNotExists($index)
    {
        Assert::assertFalse(
            $this->store->indexExists($index),
            "Failed asserting that '{$index}' search index doesn't exist."
        );

        return $this;
    }

    public function fakeRecord(Model $model, array $data, bool $merge = true, ?string $index = null): self
    {
        $this->store->mock($index ?: $model->searchableAs(), $model->getScoutKey(), $data, $merge);

        return $this;
    }

    public function fakeResponseData(array $data)
    {
        $fakeBuilder = new FakeBuilder();

        $searchResponseMock = new SearchResponseMock($fakeBuilder, $data);

        $this->engine->addSearchResponseMock($searchResponseMock);

        return $fakeBuilder;
    }
}
