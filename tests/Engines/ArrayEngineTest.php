<?php

namespace Sti3bas\ScoutArray\Tests\Engines;

use Mockery;
use Laravel\Scout\Builder;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use Sti3bas\ScoutArray\ArrayStore;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\LazyCollection;
use Sti3bas\ScoutArray\Engines\ArrayEngine;
use Sti3bas\ScoutArray\Tests\Fixtures\SearchableModel;
use Sti3bas\ScoutArray\Tests\Fixtures\EmptySearchableModel;
use Sti3bas\ScoutArray\Tests\Fixtures\SoftDeletableSearchableModel;

class ArrayEngineTest extends TestCase
{
    protected function setUp(): void
    {
        Config::shouldReceive('get')->with('scout.after_commit', Mockery::any())->andReturn(false);
    }

    protected function tearDown(): void
    {
        $this->addToAssertionCount(
            \Mockery::getContainer()->mockery_getExpectationCount()
        );

        \Mockery::close();
    }

    /** @test */
    public function it_can_search_for_the_records()
    {
        $store = new ArrayStore();
        $engine = new ArrayEngine($store);
        $engine->update(Collection::make([
            new SearchableModel(['id' => 1, 'foo' => 'bar', 'scoutKey' => '1']),
            new SearchableModel(['id' => 2, 'foo' => 'baz', 'scoutKey' => '2']),
            new SearchableModel(['id' => 3, 'foo' => 'bar', 'scoutKey' => '3']),
            new SearchableModel(['id' => 4, 'foo' => ['test' => 'barbaz'], 'scoutKey' => '4']),
        ]));

        $store->mock((new SearchableModel())->searchableAs(), '4', [
            'test' => 'foo',
        ]);

        $results = $engine->search(new Builder(new SearchableModel, 'Bar'));

        $this->assertCount(3, $results['hits']);
        $this->assertEquals(3, $results['total']);
        $this->assertEquals(['id' => 4, 'foo' => ['test' => 'barbaz'], 'test' => 'foo', 'objectID' => 4, 'scoutKey' => '4'], $results['hits'][0]);
        $this->assertEquals(['id' => 3, 'foo' => 'bar', 'objectID' => '3', 'scoutKey' => '3'], $results['hits'][1]);
        $this->assertEquals(['id' => 1, 'foo' => 'bar', 'objectID' => '1', 'scoutKey' => '1'], $results['hits'][2]);
    }

    /** @test */
    public function it_can_search_array_properties()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            new SearchableModel(['id' => 1, 'foo' => ['bar', 'baz', 'bak'], 'scoutKey' => '1']),
            new SearchableModel(['id' => 2, 'foo' => ['bar', 'derp', 'meh'], 'scoutKey' => '2']),
            new SearchableModel(['id' => 3, 'foo' => ['bak', 'bleh'], 'scoutKey' => '3']),
        ]));

        $builder = new Builder(new SearchableModel, '');
        $builder->wheres['foo'] = 'derp';

        $results = $engine->search($builder);

        $this->assertCount(1, $results['hits']);
        $this->assertEquals('2', $results['hits'][0]['objectID']);

        $builder->wheres['foo'] = 'bak';

        $results = $engine->search($builder);

        $this->assertCount(2, $results['hits']);
        $this->assertEquals('3', $results['hits'][0]['objectID']);
        $this->assertEquals('1', $results['hits'][1]['objectID']);
    }

    /** @test */
    public function it_returns_all_results_if_no_query_provided()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            new SearchableModel(['id' => 1, 'foo' => 'bar', 'scoutKey' => 1]),
            new SearchableModel(['id' => 2, 'foo' => 'baz', 'scoutKey' => 2]),
            new SearchableModel(['id' => 3, 'foo' => 'bar', 'scoutKey' => 3]),
        ]));

        $results = $engine->search(new Builder(new SearchableModel, ''));

        $this->assertCount(3, $results['hits']);
        $this->assertEquals(3, $results['total']);
    }

    /** @test */
    public function search_results_can_be_limited()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            new SearchableModel(['id' => 1, 'foo' => 'bar', 'scoutKey' => 1]),
            new SearchableModel(['id' => 2, 'foo' => 'baz', 'scoutKey' => 2]),
            new SearchableModel(['id' => 3, 'foo' => 'bar', 'scoutKey' => 3]),
        ]));

        $builder = new Builder(new SearchableModel, '');
        $builder->limit = 2;

        $results = $engine->search($builder);

        $this->assertCount(2, $results['hits']);
        $this->assertEquals(3, $results['total']);
    }

    /** @test */
    public function callback_can_be_passed_to_search()
    {
        $arrayStore = new ArrayStore();
        $engine = new ArrayEngine($arrayStore);
        $engine->update(Collection::make([
            new SearchableModel(['id' => 1, 'foo' => 'bar', 'scoutKey' => 1]),
            new SearchableModel(['id' => 2, 'foo' => 'baz', 'scoutKey' => 2]),
            new SearchableModel(['id' => 3, 'foo' => 'bar', 'scoutKey' => 3])
        ]));

        $wasCalled = false;
        $builder = new Builder(new SearchableModel, 'bar', function ($store, $index, $query) use (&$wasCalled, $arrayStore) {
            $wasCalled = true;
            $this->assertSame($store, $arrayStore);
            $this->assertEquals($index, (new SearchableModel())->searchableAs());
            $this->assertEquals('bar', $query);
        });

        $engine->search($builder);

        $this->assertTrue($wasCalled);
    }
    
    /** @test */
    public function it_returns_empty_array_if_no_results_found()
    {
        $engine = new ArrayEngine(new ArrayStore());

        $results = $engine->search(new Builder(new SearchableModel, 'test'));

        $this->assertCount(0, $results['hits']);
        $this->assertEquals(0, $results['total']);
    }

    /** @test */
    public function custom_index_can_be_passed()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 1]),
        ]));

        $results = $engine->search(new Builder(new SearchableModel, 'bar'));

        $this->assertCount(1, $results['hits']);
        $this->assertEquals(1, $results['hits'][0]['objectID']);

        $model = Mockery::mock(new SearchableModel(['foo' => 'bar', 'scoutKey' => 2]))->makePartial();
        $model->shouldReceive('searchableAs')->andReturn('test_index_2');

        $engine->update(Collection::make([
            $model,
        ]));

        $builder = new Builder(new SearchableModel, 'bar');
        $builder->index = 'test_index_2';

        $results = $engine->search($builder);

        $this->assertCount(1, $results['hits']);
        $this->assertEquals(2, $results['hits'][0]['objectID']);
    }

    /** @test */
    public function it_can_update_a_record_in_the_index()
    {
        $model = new SearchableModel(['id' => 123, 'foo' => 'bar', 'scoutKey' => 'test']);
        $model->withScoutMetadata('meta', 'test');

        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([$model]));

        $builder = new Builder(new SearchableModel, '');
        $builder->wheres = [
            'id' => 123,
        ];

        $this->assertEquals(['id' => 123, 'foo' => 'bar', 'objectID' => 'test', 'meta' => 'test', 'scoutKey' => 'test'], $engine->search($builder)['hits'][0]);

        $model->foo = 'baz';

        $engine->update(Collection::make([$model]));

        $this->assertEquals(['id' => 123, 'foo' => 'baz', 'objectID' => 'test', 'meta' => 'test', 'scoutKey' => 'test'], $engine->search($builder)['hits'][0]);
    }

    /** @test */
    public function it_can_update_soft_deletable_records_in_the_index()
    {
        $model = new SoftDeletableSearchableModel(['foo' => 'bar', 'scoutKey' => 123]);
        $model->setDateFormat('Y-m-d H:i:s');
        $model->deleted_at = '2019-01-01 12:00:00';
        $model2 = new SoftDeletableSearchableModel(['foo' => 'bar', 'scoutKey' => 234]);

        $engine = new ArrayEngine(new ArrayStore(), $softDeletesEnabled = true);
        $engine->update(Collection::make([$model, $model2]));

        $builder1 = new Builder(new SoftDeletableSearchableModel, '');
        $builder1->wheres = [
            'scoutKey' => 123,
        ];

        $builder2 = new Builder(new SoftDeletableSearchableModel, '');
        $builder2->wheres = [
            'scoutKey' => 234,
        ];

        $this->assertEquals(['foo' => 'bar', 'objectID' => '123', 'scoutKey' => 123, 'deleted_at' => '2019-01-01 12:00:00', '__soft_deleted' => 1], $engine->search($builder1)['hits'][0]);
        $this->assertEquals(['foo' => 'bar', 'objectID' => '234', 'scoutKey' => 234, '__soft_deleted' => 0], $engine->search($builder2)['hits'][0]);
    }

    /** @test */
    public function it_will_not_push_soft_delete_metadata_when_updating_if_its_not_enabled()
    {
        $model = new SoftDeletableSearchableModel(['foo' => 'bar', 'scoutKey' => 123]);
        $model->setDateFormat('Y-m-d H:i:s');
        $model->deleted_at = '2019-01-01 12:00:00';
        $model2 = new SoftDeletableSearchableModel(['foo' => 'bar', 'scoutKey' => 234]);

        $engine = new ArrayEngine(new ArrayStore(), $softDeletesEnabled = false);
        $engine->update(Collection::make([$model, $model2]));
        
        $builder1 = new Builder(new SoftDeletableSearchableModel, '');
        $builder1->wheres = [
            'scoutKey' => 123,
        ];

        $builder2 = new Builder(new SoftDeletableSearchableModel, '');
        $builder2->wheres = [
            'scoutKey' => 234,
        ];

        $this->assertEquals(['foo' => 'bar', 'objectID' => '123', 'scoutKey' => 123, 'deleted_at' => '2019-01-01 12:00:00'], $engine->search($builder1)['hits'][0]);
        $this->assertEquals(['foo' => 'bar', 'objectID' => '234', 'scoutKey' => 234], $engine->search($builder2)['hits'][0]);
    }

    /** @test */
    public function it_will_not_update_empty_records_in_the_index()
    {
        $model = new EmptySearchableModel(['scoutKey' => 123]);

        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([$model]));

        $builder = new Builder(new EmptySearchableModel, '');
        $builder->wheres = [
            'scoutKey' => 123,
        ];

        $this->assertEmpty($engine->search($builder)['hits']);
    }

    /** @test */
    public function it_can_delete_a_record_from_the_index()
    {
        $model1 = new SearchableModel(['scoutKey' => 1]);
        $model2 = new SearchableModel(['scoutKey' => 2]);
        $model3 = new SearchableModel(['scoutKey' => 3]);

        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([$model1, $model2, $model3]));

        $builder = new Builder(new SearchableModel, '');
        $builder->wheres = [
            'scoutKey' => 2,
        ];

        $this->assertCount(1, $engine->search($builder)['hits']);

        $engine->delete(Collection::make([$model2]));

        $this->assertCount(0, $engine->search($builder)['hits']);
    }

    /** @test */
    public function it_can_paginate_results()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 1]),
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 2]),
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 3])
        ]));

        $results = $engine->paginate(new Builder(new SearchableModel(), 'bar'), 1, 3);

        $this->assertCount(1, $results['hits']);
        $this->assertEquals(3, $results['total']);
        $this->assertEquals(1, $results['hits'][0]['scoutKey']);
    }

    /** @test */
    public function it_can_filter_paginated_results()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 1]),
            new SearchableModel(['foo' => 'baz', 'scoutKey' => 2]),
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 3]),
        ]));

        $builder = new Builder(new SearchableModel(), 'bar');
        $builder->wheres = [
            'foo' => 'bar',
        ];

        $results = $engine->paginate($builder, 2, 1);

        $this->assertEquals(2, $results['total']);
        $this->assertEquals(3, $results['hits'][0]['scoutKey']);
        $this->assertEquals(1, $results['hits'][1]['scoutKey']);
    }

    /** @test */
    public function it_can_map_ids()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 1]),
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 2]),
            new SearchableModel(['foo' => 'bar', 'scoutKey' => 3]),
        ]));

        $results = $engine->search(new Builder(new SearchableModel(), 'bar'));

        $ids = $engine->mapIds($results);

        $this->assertInstanceOf(Collection::class, $ids);
        $this->assertEquals([3, 2, 1], $engine->mapIds($results)->all());
    }

    /** @test */
    public function it_can_map_records_to_models()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $model = Mockery::mock(stdClass::class);
        $model->shouldReceive('getScoutModelsByIds')->andReturn($models = Collection::make([
            $model3 = new SearchableModel(['scoutKey' => 3]),
            $model1 = new SearchableModel(['scoutKey' => 1]),
            $model2 = new SearchableModel(['scoutKey' => 2]),
        ]));

        $results = $engine->map(Mockery::mock(Builder::class), ['hits' => [
            ['objectID' => 2],
            ['objectID' => 1],
            ['objectID' => 3],
        ]], $model);

        $this->assertEquals(3, count($results));
        $this->assertTrue($results[0]->is($model1));
        $this->assertTrue($results[1]->is($model2));
        $this->assertTrue($results[2]->is($model3));
    }

    /** @test */
    public function it_returns_empty_collection_if_no_results_when_mapping()
    {
        $engine = new ArrayEngine(new ArrayStore());

        $results = $engine->map(new Builder(new SearchableModel, ''), ['hits' => []], new SearchableModel);

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals(0, count($results));
    }

    /** @test */
    public function it_can_lazy_map_records_to_models()
    {
        $engine = new ArrayEngine(new ArrayStore());

        $model = Mockery::mock(stdClass::class);
        $model->shouldReceive('queryScoutModelsByIds->cursor')->andReturn($models = LazyCollection::make([
            $model3 = new SearchableModel(['scoutKey' => 3]),
            $model1 = new SearchableModel(['scoutKey' => 1]),
            $model2 = new SearchableModel(['scoutKey' => 2]),
        ]));

        $builder = Mockery::mock(Builder::class);

        $results = $engine->lazyMap($builder, ['nbHits' => 1, 'hits' => [
            ['objectID' => 2],
            ['objectID' => 1],
            ['objectID' => 3],
        ]], $model);

        $this->assertEquals(3, count($results));
        $this->assertInstanceOf(LazyCollection::class, $results);

        $this->assertTrue($results->all()[0]->is($model1));
        $this->assertTrue($results->all()[1]->is($model2));
        $this->assertTrue($results->all()[2]->is($model3));
    }

    /** @test */
    public function it_returns_empty_lazy_collection_if_no_results_when_lazy_mapping()
    {
        $engine = new ArrayEngine(new ArrayStore());

        $results = $engine->lazyMap(new Builder(new SearchableModel, ''), ['hits' => []], new SearchableModel);

        $this->assertInstanceOf(LazyCollection::class, $results);
        $this->assertEquals(0, count($results));
    }

    /** @test */
    public function it_knows_total_count()
    {
        $engine = new ArrayEngine(new ArrayStore());

        $this->assertEquals(100, $engine->getTotalCount(['total' => 100]));
    }

    /** @test */
    public function it_can_flush_all_models_records()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            $model1 = new SearchableModel(['foo' => 'bar', 'scoutKey' => 1]),
            $model2 = new SearchableModel(['foo' => 'bar', 'scoutKey' => 2]),
            $model3 = new SearchableModel(['foo' => 'bar', 'scoutKey' => 3]),
        ]));

        $builder = new Builder(new SearchableModel, '');
        $builder->wheres = [
            'foo' => 'bar',
        ];

        $this->assertCount(3, $engine->search($builder)['hits']);

        $engine->flush(new SearchableModel());

        $this->assertCount(0, $engine->search($builder)['hits']);
    }

    /** @test */
    public function it_can_be_filtered()
    {
        $engine = new ArrayEngine(new ArrayStore());
        $engine->update(Collection::make([
            new SearchableModel(['foo' => 'bar', 'x' => 'y', 'scoutKey' => 1]),
            new SearchableModel(['foo' => 'baz', 'x' => 'x', 'scoutKey' => 2]),
            new SearchableModel(['foo' => 'bar', 'x' => 'z', 'scoutKey' => 3]),
        ]));
        
        $builder = new Builder(new SearchableModel(), null);
        $builder->wheres = [
            'foo' => 'baz',
            'x' => 'x',
        ];
        $results = $engine->search($builder);

        $this->assertCount(1, $results['hits']);
        $this->assertEquals(2, $results['hits'][0]['scoutKey']);
    }

    /** @test */
    public function it_can_create_search_index()
    {
        $store = Mockery::spy(ArrayStore::class);

        $engine = new ArrayEngine($store);

        $engine->createIndex('test');

        $store->shouldHaveReceived('createIndex')->with('test')->once();
    }

    /** @test */
    public function it_can_delete_search_index()
    {
        $store = Mockery::spy(ArrayStore::class);

        $engine = new ArrayEngine($store);

        $engine->deleteIndex('test');

        $store->shouldHaveReceived('deleteIndex')->with('test')->once();
    }
}
