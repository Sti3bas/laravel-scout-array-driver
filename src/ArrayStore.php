<?php

namespace Sti3bas\ScoutArray;

use Closure;
use Illuminate\Support\Arr;

class ArrayStore
{
    protected $storage = [
        'current' => [],
        'mocks' => [],
        'history' => [],
    ];

    public function set(string $index, string $key, array $data): void
    {
        $data['objectID'] = $key;

        $this->add('current', $index, $key, $data);
        $this->add('history', $index, $key, $data, false);
    }

    public function get(string $index, string $key): ?array
    {
        return $this->getMock($index, $key) ?? $this->getRecord('current', $index, $key);
    }

    public function forget(string $index, string $key): void
    {
        $this->setData('current', $index, $this->getRecordsExcept('current', $key, $index));
    }

    public function createIndex(string $index): void
    {
        $this->setData('current', $index, []);
    }

    public function deleteIndex(string $index): void
    {
        $this->storage = Arr::except($this->storage, 'current.'.$index);
    }

    public function indexExists(string $index): bool
    {
        return Arr::exists($this->storage['current'], $index);
    }

    public function flush(string $index): void
    {
        $this->setData('current', $index, []);
    }

    private function setData(string $type, string $index, array $data): void
    {
        Arr::set($this->storage[$type], $index, $data);
    }

    public function find(string $index, Closure $callback, $mock = false): array
    {
        return $this->findInArray($mock ? $this->replaceRecordsWithMocks($index) : Arr::get($this->storage['current'], $index, []), $callback);
    }

    public function findInHistory(string $index, Closure $callback): array
    {
        return $this->findInArray(Arr::get($this->storage['history'], $index, []), $callback);
    }

    public function count(?string $index = null, string $type = 'current'): int
    {
        return count($index ? Arr::get($this->storage[$type], $index, []) : Arr::flatten($this->storage[$type], 1));
    }

    public function countInHistory(?string $index = null): int
    {
        return $this->count($index, 'history');
    }

    public function mock(string $index, string $key, array $data, bool $merge = true): void
    {
        $data = array_merge($merge ? static::get($index, $key) ?? [] : ['objectID' => $key], $data);

        $this->setData('mocks', $index, Arr::prepend($this->getRecordsExcept('current', $key, $index), $data));
    }

    public function getDefaultIndex(string $type = 'current'): string
    {
        return Arr::first(array_keys($this->storage[$type])) ?? '';
    }

    private function add(string $type, string $index, string $key, array $data, bool $removeOld = true): void
    {
        $array = &$this->storage[$type];

        if (! Arr::has($array, $index)) {
            Arr::set($array, $index, []);
        }

        $this->setData($type, $index, Arr::prepend($removeOld ? $this->getRecordsExcept('current', $key, $index) : $array[$index], $data));
    }

    private function getMock(string $index, string $key): ?array
    {
        return $this->getRecord('mocks', $index, $key);
    }

    private function getRecord(string $type, string $index, string $key): ?array
    {
        return Arr::first(array_filter(Arr::get($this->storage[$type], $index, []), function ($mock) use ($key) {
            return $mock['objectID'] === $key;
        }));
    }

    private function getRecordsExcept(string $type, string $exceptKey, string $index): array
    {
        return array_values(array_filter(Arr::get($this->storage[$type], $index, []), function ($mock) use ($exceptKey) {
            return $mock['objectID'] !== $exceptKey;
        }));
    }

    private function findInArray(array $array, Closure $callback): array
    {
        return array_values(array_filter($array, function ($record) use ($callback) {
            return $callback($record);
        }));
    }

    private function replaceRecordsWithMocks(string $index): array
    {
        return array_map(function ($record) use ($index) {
            if ($mock = $this->getMock($index, $record['objectID'])) {
                return $mock;
            }

            return $record;
        }, Arr::get($this->storage['current'], $index, []));
    }
}
