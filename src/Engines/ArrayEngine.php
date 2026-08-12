<?php

namespace Sti3bas\ScoutArray\Engines;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Sti3bas\ScoutArray\ArrayStore;

class ArrayEngine extends Engine
{
    /**
     * @var ArrayStore
     */
    public $store;

    /**
     * Determines if soft deletes for Scout are enabled or not.
     *
     * @var bool
     */
    protected $softDelete;

    public function __construct($store, $softDelete = false)
    {
        $this->store = $store;
        $this->softDelete = $softDelete;
    }

    /**
     * Update the given model in the index.
     *
     * @param  Collection  $models
     */
    public function update($models): void
    {
        if ($this->usesSoftDelete($models->first()) && $this->softDelete) {
            $models->each->pushSoftDeleteMetadata();
        }

        $models->each(function ($model): void {
            if (empty($searchableData = $model->toSearchableArray())) {
                return;
            }

            $this->store->set($model->searchableAs(), $model->getScoutKey(), array_merge(
                $searchableData,
                $model->scoutMetadata(),
                [$model->getScoutKeyName() => $model->getScoutKey()]
            ));
        });
    }

    /**
     * Remove the given model from the index.
     *
     * @param  Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $models->each(function ($model) {
            $this->store->forget($model->searchableAs(), $model->getScoutKey());
        });
    }

    /**
     * Perform the given search on the engine.
     *
     *
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, [
            'perPage' => $builder->limit,
        ]);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, [
            'perPage' => $perPage,
            'page' => $page,
        ]);
    }

    /**
     * Perform the given search on the engine.
     *
     * @return array
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $index = $builder->index ?: $builder->model->searchableAs();

        $matches = $this->store->find($index, function ($record) use ($builder) {
            $values = new RecursiveIteratorIterator(new RecursiveArrayIterator($record));

            return $this->matchesWheres($record, $builder->wheres)
                && $this->matchesKeyValueFilters($record, $builder->whereIns)
                && $this->matchesKeyValueFilters($record, $builder->whereNotIns, true)
                && ! empty(array_filter(iterator_to_array($values, false), fn ($value) => ! $builder->query || stripos($value, $builder->query) !== false));
        }, true);

        $matches = Collection::make($matches);

        if (! empty($builder->orders)) {
            $matches = $matches->sort(function ($a, $b) use ($builder) {
                foreach ($builder->orders as $order) {
                    $comparison = data_get($a, $order['column']) <=> data_get($b, $order['column']);

                    if ($comparison !== 0) {
                        return $order['direction'] === 'desc' ? -$comparison : $comparison;
                    }
                }

                return 0;
            })->values();
        }

        return [
            'hits' => (isset($options['perPage']) ? $matches->slice((($options['page'] ?? 1) - 1) * $options['perPage'], $options['perPage']) : $matches)->values()->all(),
            'total' => $matches->count(),
        ];
    }

    /**
     * Determine if the given record matches given filters.
     *
     * @param  array  $filters
     * @param  bool  $not
     */
    private function matchesWheres(array $record, array $wheres): bool
    {
        foreach ($wheres as $where) {
            $value = data_get($record, $where['field']);

            $matches = match ($where['operator']) {
                '=' => $value === $where['value'],
                '!=' => $value !== $where['value'],
                '>' => $value > $where['value'],
                '>=' => $value >= $where['value'],
                '<' => $value < $where['value'],
                '<=' => $value <= $where['value'],
                default => $value === $where['value'],
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    private function matchesKeyValueFilters(array $record, array $filters, bool $not = false): bool
    {
        if (empty($filters)) {
            return true;
        }

        $match = Collection::make($filters)->every(function ($value, $key) use ($record) {
            $needle = $this->resolveDottedValue($record, $key);

            if (is_array($needle)) {
                return ! empty(array_intersect($needle, $value));
            }

            return in_array($needle, $value, true);
        });

        return $not ? ! $match : $match;
    }

    private function resolveDottedValue(array $record, string $key)
    {
        $direct = data_get($record, $key);

        if ($direct instanceof Collection) {
            $direct = $direct->all();
        }

        if ($direct !== null) {
            return $direct;
        }

        $segments = explode('.', $key);
        $current = $record;

        foreach ($segments as $i => $segment) {
            if ($current instanceof Collection) {
                $current = $current->all();
            }

            if (is_array($current) && $this->isListArray($current)) {
                $remaining = implode('.', array_slice($segments, $i));

                return Collection::make($current)->map(fn ($item) => data_get($item, $remaining))->flatten()->all();
            }

            $current = data_get($current, $segment);

            if ($current === null) {
                return null;
            }
        }

        if ($current instanceof Collection) {
            $current = $current->all();
        }

        return $current;
    }

    /**
     * Determine if the given array is a list (PHP 8.0–compatible array_is_list).
     */
    private function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return Collection
     */
    public function mapIds($results)
    {
        return Collection::make($results['hits'])->pluck('objectID')->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  mixed  $results
     * @param  Model  $model
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if (count($results['hits']) === 0) {
            return $model->newCollection();
        }

        $objectIds = Collection::make($results['hits'])->pluck('objectID')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->getScoutModelsByIds($builder, $objectIds)
            ->filter(function ($model) use ($objectIds) {
                return in_array($model->getScoutKey(), $objectIds);
            })->sortBy(function ($model) use ($objectIdPositions) {
                return $objectIdPositions[$model->getScoutKey()];
            })->values();
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     *
     * @param  mixed  $results
     * @param  Model  $model
     * @return LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        if (count($results['hits']) === 0) {
            return LazyCollection::make($model->newCollection());
        }

        $objectIds = Collection::make($results['hits'])->pluck('objectID')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->queryScoutModelsByIds(
            $builder,
            $objectIds
        )->cursor()->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['total'];
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  Model  $model
     * @return void
     */
    public function flush($model)
    {
        $this->store->flush($model->searchableAs());
    }

    /**
     * Create a search index.
     *
     * @param  string  $name
     * @return mixed
     */
    public function createIndex($name, array $options = [])
    {
        $this->store->createIndex($name);
    }

    /**
     * Delete a search index.
     *
     * @param  string  $name
     * @return mixed
     */
    public function deleteIndex($name)
    {
        $this->store->deleteIndex($name);
    }

    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }

    protected function buildSearchQuery(Builder $builder)
    {
        $query = $this->initializeSearchQuery(
            $builder,
            array_keys($builder->model->toSearchableArray()),
            $this->getPrefixColumns($builder),
            $this->getFullTextColumns($builder)
        );

        return $this->constrainForSoftDeletes(
            $builder,
            $this->addAdditionalConstraints($builder, $query->take($builder->limit))
        );
    }
}
