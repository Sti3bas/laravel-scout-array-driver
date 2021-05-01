<?php

namespace Sti3bas\ScoutArray\Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Sti3bas\ScoutArray\ArrayStore;
use Sti3bas\ScoutArray\Search;
use Sti3bas\ScoutArray\Tests\Fixtures\SearchableModel;

class SearchTest extends TestCase
{
    /** @test */
    public function it_can_fake_a_record()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel([
            'scoutKey' => 123,
            'foo' => 'bar',
        ]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());

        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'bar'], $store->get($model->searchableAs(), $model->getScoutKey()));
        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'bar'], $store->get('custom_index', $model->getScoutKey()));

        $search->fakeRecord($model, [
            'foo' => 'baz',
        ]);

        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'baz'], $store->get($model->searchableAs(), $model->getScoutKey()));
        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'bar'], $store->get('custom_index', $model->getScoutKey()));
    }

    /** @test */
    public function it_can_fake_full_synced_record()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel([
            'scoutKey' => 123,
            'foo' => 'bar',
            'baz' => 'bar',
        ]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());

        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'bar', 'baz' => 'bar'], $store->get($model->searchableAs(), $model->getScoutKey()));
        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'bar', 'baz' => 'bar'], $store->get('custom_index', $model->getScoutKey()));

        $search->fakeRecord($model, [
            'foo' => 'baz',
        ], false);

        $this->assertEquals(['foo' => 'baz', 'objectID' => 123], $store->get($model->searchableAs(), $model->getScoutKey()));
        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'bar', 'baz' => 'bar'], $store->get('custom_index', $model->getScoutKey()));
    }

    /** @test */
    public function it_can_fake_a_record_in_the_custom_index()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel([
            'scoutKey' => 123,
            'foo' => 'bar',
        ]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());

        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'bar'], $store->get($model->searchableAs(), $model->getScoutKey()));
        $this->assertEquals(['scoutKey' => 123, 'objectID' => 123, 'foo' => 'bar'], $store->get('custom_index', $model->getScoutKey()));

        $search->fakeRecord($model, [
            'foo' => 'baz',
        ], false, 'custom_index');

        $this->assertEquals(['foo' => 'baz', 'objectID' => 123], $store->get('custom_index', $model->getScoutKey()));
        $this->assertEquals(['scoutKey' => 123, 'foo' => 'bar', 'objectID' => 123], $store->get($model->searchableAs(), $model->getScoutKey()));
    }

    /** @test */
    public function assert_contains_passes_if_record_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());

        $result = $search->assertContains($model);

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_contains_fails_if_record_does_not_exist()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);

        $search->assertContains($model);
    }

    /** @test */
    public function assert_contains_passes_if_callback_returns_true()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertContains($model, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_contains_fails_if_callback_returns_false()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertContains($model, function ($record) {
            return $record['foo'] === 'baz';
        });
    }

    /** @test */
    public function assert_not_contains_passes_if_record_does_not_exist()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $result = $search->assertNotContains($model);

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_not_contains_fails_if_record_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertNotContains($model);
    }

    /** @test */
    public function assert_not_contains_passes_if_callback_returns_false()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertNotContains($model, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_not_contains_fails_if_callback_returns_true()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertNotContains($model, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_contains_in_passes_if_record_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());

        $result = $search->assertContainsIn('custom_index', $model);

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_contains_in_fails_if_record_does_not_exist()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertContainsIn('custom_index', $model);
    }

    /** @test */
    public function assert_contains_in_passes_if_callback_returns_true()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertContainsIn('custom_index', $model, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_contains_in_fails_if_callback_returns_false()
    {
        $this->expectException(AssertionFailedError::class);
         
        $store = new ArrayStore;
        $search = new Search($store);
 
        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
 
        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());
 
        $search->assertContainsIn('custom_index', $model, function ($record) {
            return $record['foo'] === 'baz';
        });
    }

    /** @test */
    public function assert_not_contains_in_passes_if_record_doesn_not_exist()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set($model->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());

        $result = $search->assertNotContainsIn('custom_index', $model);

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_not_contains_in_fails_if_record_exists()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertNotContainsIn('custom_index', $model);
    }

    /** @test */
    public function assert_not_contains_in_passes_if_callback_returns_false()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertNotContainsIn('custom_index', $model, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_not_contains_in_fails_if_callback_returns_true()
    {
        $this->expectException(AssertionFailedError::class);
         
        $store = new ArrayStore;
        $search = new Search($store);
 
        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
 
        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());
 
        $search->assertNotContainsIn('custom_index', $model, function ($record) {
            return $record['foo'] === 'baz';
        });
    }

    /** @test */
    public function assert_empty_passes_if_no_records_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);
 
        $result = $search->assertEmpty();

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_empty_fails_if_record_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test', 'test', ['foo' => 'bar']);
 
        $search->assertEmpty();
    }
    
    /** @test */
    public function assert_empty_in_passes_if_no_records_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test', 'test', ['foo' => 'bar']);
 
        $result = $search->assertEmptyIn('test2');

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_empty_in_fails_if_record_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test', 'test', ['foo' => 'bar']);
 
        $search->assertEmptyIn('test');
    }

    /** @test */
    public function assert_not_empty_passes_if_record_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test', 'test', [
            'foo' => 'bar',
        ]);
 
        $result = $search->assertNotEmpty();

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_not_empty_fails_if_no_records_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $search->assertNotEmpty();
    }

    /** @test */
    public function assert_not_empty_in_passes_if_record_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test', 'test', [
            'foo' => 'bar',
        ]);
 
        $result = $search->assertNotEmptyIn('test');

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_not_empty_in_fails_if_no_records_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test2', 'test', [
            'foo' => 'bar',
        ]);
 
        $search->assertNotEmptyIn('test');
    }

    /** @test */
    public function assert_synced_passes_if_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $result = $search->assertSynced($model);
        $search->assertSynced($model2);

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_synced_fails_if_no_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertSynced($model);
    }

    /** @test */
    public function assert_synced_passes_if_callback_returns_true_and_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);
        $model3 = new SearchableModel(['scoutKey' => 456, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set($model3->searchableAs(), $model3->getScoutKey(), $model3->toSearchableArray());
        
        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertSynced($model, function ($record) {
            return $record['foo'] === 'bar';
        });

        $search->assertSynced($model3, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_synced_fails_if_callback_returns_false_and_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
        $model3 = new SearchableModel(['scoutKey' => 456, 'foo' => 'bar']);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set($model3->searchableAs(), $model3->getScoutKey(), $model3->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertSynced($model, function ($record) {
            return $record['foo'] === 'baz';
        });

        $search->assertSynced($model3, function ($record) {
            return $record['foo'] === 'baz';
        });
    }

    /** @test */
    public function assert_not_synced_passes_if_record_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $result = $search->assertNotSynced($model);

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_not_synced_passes_if_no_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());

        $search->assertNotSynced($model);
    }

    /** @test */
    public function assert_not_synced_fails_if_record_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        
        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertNotSynced($model);
    }

    /** @test */
    public function assert_not_synced_passes_if_callback_returns_false_and_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);
        $model3 = new SearchableModel(['scoutKey' => 456, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set($model3->searchableAs(), $model3->getScoutKey(), $model3->toSearchableArray());
        
        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertNotSynced($model, function ($record) {
            return $record['foo'] === 'baz';
        });

        $search->assertNotSynced($model3, function ($record) {
            return $record['foo'] === 'baz';
        });
    }

    /** @test */
    public function assert_not_synced_fails_if_callback_returns_true_and_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);
        
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
        $model3 = new SearchableModel(['scoutKey' => 456, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set($model3->searchableAs(), $model3->getScoutKey(), $model3->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertNotSynced($model, function ($record) {
            return $record['foo'] === 'bar';
        });

        $search->assertNotSynced($model3, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_synced_to_passes_if_record_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);
        $model3 = new SearchableModel(['scoutKey' => 456]);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index2', $model3->getScoutKey(), $model3->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $result = $search->assertSyncedTo('custom_index', $model);
        $search->assertSyncedTo('custom_index2', $model3);

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_synced_to_fails_if_no_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);
        $model3 = new SearchableModel(['scoutKey' => 456]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertSyncedTo('custom_index', $model);
    }

    /** @test */
    public function assert_synced_to_passes_if_callback_returns_true_and_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
        $model3 = new SearchableModel(['scoutKey' => 456, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index2', $model3->getScoutKey(), $model3->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertSyncedTo('custom_index', $model, function ($record) {
            return $record['foo'] === 'bar';
        });

        $search->assertSyncedTo('custom_index2', $model3, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_synced_to_fails_if_callback_returns_false_and_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
        $model3 = new SearchableModel(['scoutKey' => 456, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index2', $model3->getScoutKey(), $model3->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertSyncedTo('custom_index', $model, function ($record) {
            return $record['foo'] === 'bar';
        });

        $search->assertSyncedTo('custom_index2', $model3, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_not_synced_to_passes_if_no_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $result = $search->assertNotSyncedTo('custom_index', $model);

        $this->assertInstanceOf(Search::class, $result);
    }
    
    /** @test */
    public function assert_not_synced_to_fails_if_record_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123]);
        $model2 = new SearchableModel(['scoutKey' => 234]);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertNotSyncedTo('custom_index', $model);
    }
    
    /** @test */
    public function assert_not_synced_to_passes_if_callback_returns_false_and_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
        $model3 = new SearchableModel(['scoutKey' => 456, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index2', $model3->getScoutKey(), $model3->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertNotSyncedTo('custom_index', $model, function ($record) {
            return $record['foo'] === 'baz';
        });

        $search->assertNotSyncedTo('custom_index2', $model3, function ($record) {
            return $record['foo'] === 'baz';
        });
    }

    /** @test */
    public function assert_not_synced_to_fails_if_callback_returns_true_and_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
        $model3 = new SearchableModel(['scoutKey' => 456, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index2', $model3->getScoutKey(), $model3->toSearchableArray());

        $store->forget($model->searchableAs(), $model->getScoutKey());

        $search->assertNotSyncedTo('custom_index', $model, function ($record) {
            return $record['foo'] === 'baz';
        });

        $search->assertNotSyncedTo('custom_index2', $model3, function ($record) {
            return $record['foo'] === 'baz';
        });
    }

    /** @test */
    public function assert_synced_times_passes_if_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $result = $search->assertSyncedTimes($model, 1);

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_synced_times_fails_if_no_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertSyncedTimes($model, 1);
    }

    /** @test */
    public function assert_synced_times_fails_if_more_records_than_expected_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->set($model->searchableAs(), $model->getScoutKey(), [
            'test' => 'foo',
        ]);

        $search->assertSyncedTimes($model, 1);
    }

    /** @test */
    public function assert_synced_times_fails_if_less_records_than_expected_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->set($model->searchableAs(), $model->getScoutKey(), [
            'test' => 'foo',
        ]);

        $search->assertSyncedTimes($model, 1);
    }

    /** @test */
    public function assert_synced_times_passes_if_callback_returns_true_and_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
        $model3 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set($model3->searchableAs(), $model3->getScoutKey(), $model3->toSearchableArray());

        $store->set($model->searchableAs(), $model->getScoutKey(), [
            'foo' => 'baz',
        ]);

        $store->set($model->searchableAs(), $model->getScoutKey(), [
            'foo' => 'bar',
        ]);

        $search->assertSyncedTimes($model, 2, function ($record) {
            return $record['foo'] === 'bar';
        });

        $search->assertSyncedTimes($model3, 1, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_synced_times_fails_if_callback_returns_false_and_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);
        $model3 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set($model3->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->set($model->searchableAs(), $model->getScoutKey(), [
            'foo' => 'baz',
        ]);

        $search->assertSyncedTimes($model, 1, function ($record) {
            return $record['foo'] === 'bar';
        });

        $search->assertSyncedTimes($model3, 1, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_synced_times_to_passes_if_records_exists_in_history()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertSyncedTimesTo('custom_index', $model, 1);
    }

    /** @test */
    public function assert_synced_times_to_fails_if_no_records_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $search->assertSyncedTimesTo('custom_index', $model, 1);
    }

    /** @test */
    public function assert_synced_times_to_fails_if_more_records_than_expected_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->set('custom_index', $model->getScoutKey(), [
            'test' => 'foo',
        ]);

        $search->assertSyncedTimesTo('custom_index', $model, 1);
    }

    /** @test */
    public function assert_synced_times_to_fails_if_less_records_than_expected_exists_in_history()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->set('custom_index', $model->getScoutKey(), [
            'test' => 'foo',
        ]);

        $search->assertSyncedTimesTo('custom_index', $model, 3);
    }

    /** @test */
    public function assert_synced_times_to_passes_if_callback_returns_true_and_records_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'bar']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);
        $model3 = new SearchableModel(['scoutKey' => 234, 'foo' => 'bar']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());
        $store->set('custom_index', $model3->getScoutKey(), $model3->toSearchableArray());

        $store->set('custom_index', $model->getScoutKey(), [
            'foo' => 'baz',
        ]);

        $store->set('custom_index', $model->getScoutKey(), [
            'foo' => 'bar',
        ]);

        $search->assertSyncedTimesTo('custom_index', $model, 2, function ($record) {
            return $record['foo'] === 'bar';
        });

        $search->assertSyncedTimesTo('custom_index', $model3, 1, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_synced_times_to_fails_if_callback_returns_false_and_records_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $model = new SearchableModel(['scoutKey' => 123, 'foo' => 'baz']);
        $model2 = new SearchableModel(['scoutKey' => 234, 'foo' => 'baz']);

        $store->set($model->searchableAs(), $model->getScoutKey(), $model->toSearchableArray());
        $store->set('custom_index', $model->getScoutKey(), $model->toSearchableArray());
        $store->set($model2->searchableAs(), $model2->getScoutKey(), $model2->toSearchableArray());

        $store->set($model->searchableAs(), $model->getScoutKey(), [
            'foo' => 'baz',
        ]);

        $search->assertSyncedTimesTo('custom_index', $model, 1, function ($record) {
            return $record['foo'] === 'bar';
        });
    }

    /** @test */
    public function assert_nothing_synced_passes_if_no_records_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $result = $search->assertNothingSynced();

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_nothing_synced_fails_if_records_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test', 'test', ['foo' => 'bar']);

        $search->assertNothingSynced();
    }
    
    /** @test */
    public function assert_nothing_synced_to_passes_if_no_records_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test', 'test', ['foo' => 'bar']);

        $result = $search->assertNothingSyncedTo('custom_index');

        $this->assertInstanceOf(Search::class, $result);
    }

    /** @test */
    public function assert_nothing_synced_to_fails_if_records_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $store->set('test', 'test', ['foo' => 'bar']);

        $search->assertNothingSyncedTo('test');
    }

    /** @test */
    public function assert_index_exists_passes_if_index_exists()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $store->createIndex('test2');
        $store->createIndex('test');

        $this->assertInstanceOf(Search::class, $search->assertIndexExists('test'));
    }

    /** @test */
    public function assert_index_exists_fails_if_index_does_not_exist()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $store->createIndex('test2');

        $search->assertIndexExists('test');
    }

    /** @test */
    public function assert_index_not_exists_passes_if_index_does_not_exist()
    {
        $store = new ArrayStore;
        $search = new Search($store);

        $store->createIndex('test2');

        $this->assertInstanceOf(Search::class, $search->assertIndexNotExists('test'));
    }

    /** @test */
    public function assert_index_not_exists_fails_if_index_exists()
    {
        $this->expectException(AssertionFailedError::class);

        $store = new ArrayStore;
        $search = new Search($store);

        $store->createIndex('test2');
        $store->createIndex('test');

        $search->assertIndexNotExists('test');
    }
}
