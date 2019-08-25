<?php

namespace Sti3bas\ScoutArray\Engines;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

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
     * @param Collection $models
     *
     * @return void
     */
    public function update($models)
    {
        if ($this->usesSoftDelete($models->first()) && $this->softDelete) {
            $models->each->pushSoftDeleteMetadata();
        }

        $models->each(function ($model) {
            if (empty($searchableData = $model->toSearchableArray())) {
                return;
            }

            $this->store->set($model->searchableAs(), $model->getScoutKey(), array_merge(
                $searchableData,
                $model->scoutMetadata(),
            ));
        });
    }

    /**
     * Remove the given model from the index.
     *
     * @param Collection $models
     *
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
     * @param Builder $builder
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
     * @param  \Laravel\Scout\Builder  $builder
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
     * @param  \Laravel\Scout\Builder  $builder
     * @param  array  $options
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $index = $builder->index ?: $builder->model->searchableAs();

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->store,
                $index,
                $builder->query,
                $options
            );
        }

        $matches = $this->store->find($index, function ($record) use ($builder) {
            $values = new RecursiveIteratorIterator(new RecursiveArrayIterator($record));

            return $this->matchesFilters($record, $builder->wheres) && !empty(array_filter(iterator_to_array($values, false), function ($value) use ($builder) {
                return !$builder->query || stripos($value, $builder->query) !== false;
            }));
        }, true);
        
        $matches = Collection::make($matches);

        return [
            'hits' => (isset($options['perPage']) ? $matches->slice(($options['page'] ?? 1) - 1, $options['perPage']) : $matches)->values()->all(),
            'total' => $matches->count(),
        ];
    }

    /**
     * Determine if the given record matches given filters.
     *
     * @param array $record
     * @param array $filters
     * @return bool
     */
    private function matchesFilters($record, $filters)
    {
        if (empty($filters)) {
            return true;
        }

        return Collection::make($filters)->every(function ($value, $key) use ($record) {
            return $record[$key] === $value;
        });
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param mixed $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return Collection::make($results['hits'])->pluck('objectID')->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param mixed                               $results
     * @param \Illuminate\Database\Eloquent\Model $model
     *
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
     * Get the total count from a raw result returned by the engine.
     *
     * @param mixed $results
     *
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['total'];
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function flush($model)
    {
        $this->store->flush($model->searchableAs());
    }

    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }
}
