<?php

namespace Sti3bas\ScoutArray\Tests;

use Orchestra\Testbench\TestCase;
use Sti3bas\ScoutArray\ArrayStore;

class ArrayStoreTest extends TestCase
{
    /** @test */
    public function it_can_set_and_get_a_record()
    {
        $store = new ArrayStore();

        $this->assertNull($store->get('test_index', 'key'));

        $store->set('test_index', 'key', [
            'foo' => 'bar',
        ]);

        $this->assertEquals(['foo' => 'bar', 'objectID' => 'key'], $store->get('test_index', 'key'));
    }

    /** @test */
    public function it_stores_a_record_in_history_when_setting_a_record()
    {
        $store = new ArrayStore();

        $store->set('test_index', 'key', [
            'foo' => 'old',
        ]);

        $store->set('test_index2', 'key', [
            'foo' => 'bar',
        ]);

        $store->set('test_index', 'key', [
            'foo' => 'new',
        ]);

        $historyRecords = $store->findInHistory('test_index', function ($record) {
            return $record['objectID'] === 'key';
        });

        $this->assertCount(2, $historyRecords);
        $this->assertEquals('new', $historyRecords[0]['foo']);
        $this->assertEquals('old', $historyRecords[1]['foo']);
    }

    /** @test */
    public function it_replaces_record_with_mock_when_getting_a_record()
    {
        $store = new ArrayStore();

        $store->set('test_index', 'key', [
            'foo' => 'bar',
        ]);

        $store->mock('test_index', 'key', [
            'foo' => 'mocked',
        ]);

        $this->assertEquals(['foo' => 'mocked', 'objectID' => 'key'], $store->get('test_index', 'key'));
    }

    /** @test */
    public function it_can_forget_a_record()
    {
        $store = new ArrayStore();

        $store->set('test_index', 'key', [
            'foo' => 'bar',
        ]);

        $store->set('test_index2', 'key', [
            'foo' => 'bar',
        ]);

        $this->assertNotNull($store->get('test_index', 'key'));
        $this->assertNotNull($store->get('test_index2', 'key'));

        $store->forget('test_index', 'key');

        $this->assertNull($store->get('test_index', 'key'));
        $this->assertNotNull($store->get('test_index2', 'key'));
    }

    /** @test */
    public function it_can_flush_all_records_for_the_index()
    {
        $store = new ArrayStore();

        $store->set('test_index', 'key', [
            'foo' => 'bar',
        ]);

        $store->set('test_index', 'key2', [
            'foo' => 'baz',
        ]);

        $store->set('test_index2', 'key', [
            'foo' => 'bar',
        ]);

        $this->assertNotNull($store->get('test_index', 'key'));
        $this->assertNotNull($store->get('test_index', 'key2'));
        $this->assertNotNull($store->get('test_index2', 'key'));

        $store->flush('test_index');

        $this->assertNull($store->get('test_index', 'key'));
        $this->assertNull($store->get('test_index', 'key2'));
        $this->assertNotNull($store->get('test_index2', 'key'));
    }

    /** @test */
    public function it_can_find_records()
    {
        $store = new ArrayStore();

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $store->set('test_index2', 'key1', [
            'name' => 'bar',
        ]);

        $store->set('test_index', 'key2', [
            'name' => 'baz',
        ]);

        $store->set('test_index', 'key3', [
            'name' => 'test',
        ]);

        $store->mock('test_index', 'key2', [
            'name' => 'test',
        ]);

        $foundRecords = $store->find('test_index', function ($record) {
            return $record['name'] === 'test';
        });

        $this->assertCount(2, $foundRecords);
        $this->assertEquals('key3', $foundRecords[0]['objectID']);
        $this->assertEquals('key1', $foundRecords[1]['objectID']);
    }

    /** @test */
    public function it_can_find_records_in_history()
    {
        $store = new ArrayStore();

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $store->set('test_index2', 'key1', [
            'name' => 'bar',
        ]);

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $store->set('test_index', 'key2', [
            'name' => 'baz',
        ]);

        $store->set('test_index', 'key3', [
            'name' => 'test',
        ]);

        $store->mock('test_index', 'key2', [
            'name' => 'test',
        ]);

        $foundedRecords = $store->findInHistory('test_index', function ($record) {
            return $record['name'] === 'test';
        });

        $this->assertCount(3, $foundedRecords);
        $this->assertEquals('key3', $foundedRecords[0]['objectID']);
        $this->assertEquals('key1', $foundedRecords[1]['objectID']);
        $this->assertEquals('key1', $foundedRecords[2]['objectID']);
    }

    /** @test */
    public function it_can_count_all_records()
    {
        $store = new ArrayStore();

        $this->assertEquals(0, $store->count());

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(1, $store->count());

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(1, $store->count());

        $store->set('test_index', 'key2', [
            'name' => 'test',
        ]);

        $this->assertEquals(2, $store->count());

        $store->set('test_index2', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(3, $store->count());
    }

    /** @test */
    public function it_can_count_records_in_the_given_index()
    {
        $store = new ArrayStore();

        $this->assertEquals(0, $store->count('test_index'));

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(1, $store->count('test_index'));

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(1, $store->count('test_index'));

        $store->set('test_index', 'key2', [
            'name' => 'test',
        ]);

        $this->assertEquals(2, $store->count('test_index'));

        $store->set('test_index2', 'key', [
            'name' => 'test',
        ]);

        $this->assertEquals(2, $store->count('test_index'));
    }

    /** @test */
    public function it_can_count_all_history_records()
    {
        $store = new ArrayStore();

        $this->assertEquals(0, $store->countInHistory());

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(1, $store->countInHistory());

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(2, $store->countInHistory());

        $store->set('test_index', 'key2', [
            'name' => 'test',
        ]);

        $this->assertEquals(3, $store->countInHistory());

        $store->set('test_index2', 'key', [
            'name' => 'test',
        ]);

        $this->assertEquals(4, $store->countInHistory());
    }

    /** @test */
    public function it_can_count_all_history_records_in_the_given_index()
    {
        $store = new ArrayStore();

        $this->assertEquals(0, $store->countInHistory('test_index'));

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(1, $store->countInHistory('test_index'));

        $store->set('test_index', 'key1', [
            'name' => 'test',
        ]);

        $this->assertEquals(2, $store->countInHistory('test_index'));

        $store->set('test_index', 'key2', [
            'name' => 'test',
        ]);

        $this->assertEquals(3, $store->countInHistory('test_index'));

        $store->set('test_index2', 'key', [
            'name' => 'test',
        ]);

        $this->assertEquals(3, $store->countInHistory('test_index'));
    }

    /** @test */
    public function it_can_mock_a_record()
    {
        $store = new ArrayStore();
        $store->set('test_index', 'key', [
            'foo' => 'bar',
        ]);

        $this->assertEquals('bar', $store->get('test_index', 'key')['foo']);

        $store->mock('test_index', 'key', [
            'foo' => 'mocked',
        ]);

        $this->assertEquals('mocked', $store->get('test_index', 'key')['foo']);
    }

    /** @test */
    public function it_can_replace_all_record_when_mocking_a_record()
    {
        $store = new ArrayStore();
        $store->set('test_index', 'key', [
            'foo' => 'bar',
            'baz' => 'bar',
        ]);

        $store->mock('test_index', 'key', [
            'foo' => 'mocked',
        ], false);

        $this->assertEquals(['foo' => 'mocked', 'objectID' => 'key'], $store->get('test_index', 'key'));
    }

    /** @test */
    public function it_can_create_search_index()
    {
        $store = new ArrayStore();

        $this->assertFalse($store->indexExists('test'));

        $store->createIndex('test');

        $this->assertTrue($store->indexExists('test'));
    }

    /** @test */
    public function it_can_delete_search_index()
    {
        $store = new ArrayStore();

        $store->createIndex('test');
        $store->createIndex('test2');

        $this->assertTrue($store->indexExists('test'));
        $this->assertTrue($store->indexExists('test2'));

        $store->deleteIndex('test');

        $this->assertFalse($store->indexExists('test'));
        $this->assertTrue($store->indexExists('test2'));
    }
}
