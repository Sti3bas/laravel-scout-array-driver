<?php

namespace Sti3bas\ScoutArray;

use Closure;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Assert;

class Search
{
    protected $store;

    public function __construct(ArrayStore $store)
    {
        $this->store = $store;
    }

    public function assertContains(Model $model, Closure $callback = null): self
    {
        Assert::assertCount(
            1,
            $this->store->find($model->searchableAs(), function ($record) use ($model, $callback) {
                return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model exists in '{$model->searchableAs()}' search index."
        );

        return $this;
    }

    public function assertNotContains(Model $model, Closure $callback = null): self
    {
        Assert::assertFalse(
            count($this->store->find($model->searchableAs(), function ($record) use ($model, $callback) {
                return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
            })) === 1,
            "Failed asserting that model doesn't exist in '{$model->searchableAs()}' search index."
        );

        return $this;
    }

    public function assertContainsIn(string $index, Model $model, Closure $callback = null): self
    {
        Assert::assertCount(
            1,
            $this->store->find($index, function ($record) use ($model, $callback) {
                return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model exists in '{$index}' search index."
        );

        return $this;
    }

    public function assertNotContainsIn(string $index, Model $model, Closure $callback = null): self
    {
        Assert::assertFalse(
            count($this->store->find($index, function ($record) use ($model, $callback) {
                return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
            })) === 1,
            "Failed asserting that model doesn't exist in '{$index}' search index."
        );

        return $this;
    }

    public function assertEmpty(): self
    {
        Assert::assertEquals(0, $this->store->count(), "Failed asserting that all search indexes are empty.");

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
            "Failed asserting that search index is not empty."
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

    public function assertSynced(Model $model, Closure $callback = null): self
    {
        Assert::assertNotEmpty(
            $this->store->findInHistory($model->searchableAs(), function ($record) use ($model, $callback) {
                return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model was synced to '{$model->searchableAs()}' search index."
        );

        return $this;
    }

    public function assertNotSynced(Model $model, Closure $callback = null): self
    {
        Assert::assertCount(
            0,
            $this->store->findInHistory($model->searchableAs(), function ($record) use ($model, $callback) {
                return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model was not synced to '{$model->searchableAs()}' search index."
        );

        return $this;
    }

    public function assertSyncedTo(string $index, Model $model, Closure $callback = null): self
    {
        Assert::assertNotEmpty(
            $this->store->findInHistory($index, function ($record) use ($model, $callback) {
                return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model was synced to '{$index}' search index."
        );

        return $this;
    }

    public function assertNotSyncedTo(string $index, Model $model, Closure $callback = null): self
    {
        Assert::assertEmpty(
            $this->store->findInHistory($index, function ($record) use ($model, $callback) {
                return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
            }),
            "Failed asserting that model was not synced to '{$index}' search index."
        );

        return $this;
    }

    public function assertSyncedTimes(Model $model, int $times, Closure $callback = null): self
    {
        $syncedTimes = count($this->store->findInHistory($model->searchableAs(), function ($record) use ($model, $callback) {
            return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
        }));

        Assert::assertTrue(
            $syncedTimes === $times,
            "Failed asserting that model was synced to '{$model->searchableAs()}' search index {$times} times. It was synced {$syncedTimes} instead."
        );

        return $this;
    }

    public function assertSyncedTimesTo(string $index, Model $model, int $times, Closure $callback = null): self
    {
        $syncedTimes = count($this->store->findInHistory($index, function ($record) use ($model, $callback) {
            return $record['objectID'] === (string)$model->getScoutKey() && ($callback ? $callback($record) : true);
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
            "Failed asserting that nothing was synced to search index."
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

    public function fakeRecord(Model $model, array $data, bool $merge = true, string $index = null): self
    {
        $this->store->mock($index ?: $model->searchableAs(), $model->getScoutKey(), $data, $merge);

        return $this;
    }
}
