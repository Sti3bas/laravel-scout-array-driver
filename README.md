# Array driver for Laravel Scout

This package adds `array` driver to Laravel Scout and provides custom PHPUnit assertions to make testing search related functionality easier.

## Contents

- [Installation](#installation)
- [Usage](#usage)
- [License](#license)

## Installation

Install the package via Composer:

``` bash
composer require sti3bas/laravel-scout-array-driver --dev
```

Set Scout driver to `array` in `.env.testing` file:

```
SCOUT_DRIVER=array
```

or in `phpunit.xml` file:

``` xml
<env name="SCOUT_DRIVER" value="array"/>
```

## Usage

The [`Search`] facade provides the following methods and assertions: 

### assertContains($model, $callback = null)

Checks if model exists in the search index.

``` php
$user = factory(User::class)->create([
    'name' => 'Oliver',
]);

$user2 = User::withoutSyncingToSearch(function () {
    return factory(User::class)->create([
        'name' => 'John',
    ]);
});

Search::assertContains($user) // passes
    ->assertContains($user2) // fails
    ->assertContains($user, function ($record) { // passes
        return $record['name'] === 'Oliver';
    })
    ->assertContains($user, function ($record) { // fails
        return $record['name'] === 'John';
    })
    ->assertContains($user2, function ($record) { // fails
        return $record['name'] === 'John';
    });
```

### assertNotContains($model, $callback = null)

Checks if model doesn't exist in the search index.

``` php
$user = factory(User::class)->create([
    'name' => 'Oliver',
]);

$user2 = User::withoutSyncingToSearch(function () {
    return factory(User::class)->create([
        'name' => 'John',
    ]);
});

Search::assertNotContains($user) // fails
    ->assertNotContains($user2) // passes
    ->assertNotContains($user, function ($record) { // fails
        return $record['name'] === 'Oliver';
    })
    ->assertNotContains($user, function ($record) { // passes
        return $record['name'] === 'John';
    })
    ->assertNotContains($user2, function ($record) { // passes
        return $record['name'] === 'John';
    });
```

### assertContainsIn($index, $model, $callback = null)

Checks if model exists in custom search index.

``` php
$user = factory(User::class)->create([
    'name' => 'Oliver',
]);

Search::assertContainsIn('users', $user) // passes
    ->assertContainsIn('non_existing_index', $user) // fails
    ->assertContainsIn('users', $user, function ($record) { // passes
        return $record['name'] === 'Oliver';
    })
    ->assertContainsIn('users', $user, function ($record) { // fails
        return $record['name'] === 'John';
    });
```

### assertNotContainsIn($index, $model, $callback = null)

Checks if model doesn't exist in custom search index.

``` php
$user = factory(User::class)->create([
    'name' => 'Oliver',
]);

Search::assertNotContainsIn('users', $user) // fails
    ->assertNotContainsIn('non_existing_index', $user) // passes
    ->assertNotContainsIn('users', $user, function ($record) { // fails
        return $record['name'] === 'Oliver';
    })
    ->assertNotContainsIn('users', $user, function ($record) { // passes
        return $record['name'] === 'John';
    });
```

### assertEmpty()

Checks if all search indexes are empty.

``` php
Search::assertEmpty(); // passes

factory(User::class)->create();

Search::assertEmpty(); // fails
```

### assertEmptyIn($index)

Checks if search index is empty.

``` php
Search::assertEmptyIn('users'); // passes

factory(User::class)->create();

Search::assertEmptyIn('users'); // fails
```

### assertNotEmpty()

Checks if there is at least one record in any of search indexes.

``` php
Search::assertNotEmpty(); // fails

factory(User::class)->create();

Search::assertNotEmpty(); // passes
```

### assertNotEmptyIn($index)

Checks if search index is not empty.

``` php
Search::assertNotEmptyIn('users'); // fails

factory(User::class)->create();

Search::assertNotEmptyIn('users'); // passes
```

### assertSynced($model, $callback = null)

Checks if model was synced to search index. This assertion checks every record of the given model which was synced during the request.

``` php
$user = factory(User::class)->create([
    'name' => 'Peter',
]);

Search::assertSynced($user); // passes

$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // fails
    ->assertSynced($user) // passes
    ->assertSynced($user, function ($record) { // passes
        return $record['name'] === 'Peter';
    })
    ->assertSynced($user, function ($record) { // passes
        return $record['name'] === 'John';
    })
    ->assertSynced($user, function ($record) { // fails
        return $record['name'] === 'Oliver';
    });
```

### assertNotSynced($model, $callback = null)

Checks if model wasn't synced to search index. This assertion checks every record of the given model which was synced during the request.

``` php
$user = factory(User::class)->create([
    'name' => 'Peter',
]);

Search::assertNotSynced($user); // fails

$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // fails
    ->assertNotSynced($user); // fails

Search::assertNotSynced($user, function ($record) { // fails
    return $record['name'] === 'Peter';
})
->assertNotSynced($user, function ($record) { // fails
    return $record['name'] === 'John';
})
->assertNotSynced($user, function ($record) { // passes
    return $record['name'] === 'Oliver';
});
```

### assertSyncedTo($model, $callback = null)

Checks if model was synced to custom search index. This assertion checks every record of the given model which was synced during the request.

``` php
$user = factory(User::class)->create([
    'name' => 'Peter',
]);

Search::assertSyncedTo('users', $user); // passes

$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // fails
    ->assertSyncedTo('users', $user) // passes
    ->assertSyncedTo('users', $user, function ($record) {
        return $record['name'] === 'Peter'; // passes
    })
    ->assertSyncedTo('users', $user, function ($record) {
        return $record['name'] === 'John'; // passes
    })
    ->assertSyncedTo('non_existing_index', $user, function ($record) {
        return $record['name'] === 'John'; // fails
    });
```

### assertNotSyncedTo($model, $callback = null)

Checks if model wasn't synced to custom search index. This assertion checks every record of the given model which was synced during the request.

``` php
$user = factory(User::class)->create([
    'name' => 'Peter',
]);

Search::assertNotSyncedTo('users', $user) // fails
    ->assertNotSyncedTo('not_existing_index', $user); // passes

$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // fails
    ->assertNotSyncedTo('users', $user) // fails
    ->assertNotSyncedTo('users', $user, function ($record) {
        return $record['name'] === 'Peter'; // fails
    })
    ->assertNotSyncedTo('users', $user, function ($record) {
        return $record['name'] === 'Oliver'; // passes
    });
```

### assertSyncedTimes($model, $callback = null)

Checks if model was synced expected number of times. This assertion checks every record of the given model which was synced during the request.

``` php
$user = User::withoutSyncingToSearch(function () {
    return factory(User::class)->create([
        'name' => 'Peter',
    ]);
});

Search::assertSyncedTimes($user, 0) // passes
    ->assertSyncedTimes($user, 1); // fails

$user->searchable();
$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // fails
    ->assertSyncedTimes($user, 2) // passes
    ->assertSyncedTimes($user, 1, function ($record) {
        return $record['name'] === 'Peter'; // passes
    })
    ->assertSyncedTimes($user, 1, function ($record) {
        return $record['name'] === 'John'; // passes
    })
    ->assertSyncedTimes($user, 1, function ($record) {
        return $record['name'] === 'Oliver'; // fails
    });
```

### assertSyncedTimesTo($index, $model, $callback = null)

Checks if model was synced to custom search index expected number of times. This assertion checks every record of the given model which was synced during the request.

``` php
$user = User::withoutSyncingToSearch(function () {
    return factory(User::class)->create([
        'name' => 'Peter',
    ]);
});

Search::assertSyncedTimesTo('users', $user, 0) // passes
    ->assertSyncedTimesTo('non_existing_index', $user, 1); // fails

$user->searchable();
$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // fails
    ->assertSyncedTimesTo('users', $user, 2) // passes
    ->assertSyncedTimesTo('users', $user, 1, function ($record) {
        return $record['name'] === 'Peter'; // passes
    })
    ->assertSyncedTimesTo('non_existing_index', 1, function ($record) {
        return $record['name'] === 'John'; // fails
    });
```

### assertNothingSynced()

Checks if nothing was synced to any of search indexes. This assertion checks every record which was synced during the request.

``` php
Search::assertNothingSynced(); // passes

factory(User::class)->create();

Search::assertNothingSynced(); // fails
```

### assertNothingSyncedTo()

Checks if nothing was synced to custom search index. This assertion checks every record which was synced during the request.

``` php
Search::assertNothingSyncedTo('users'); // passes

factory(User::class)->create();

Search::assertNothingSyncedTo('users'); // fails
```

### fakeRecord($model, $data, $merge = true, $index = null)

This method allows to fake search index record of the model. It will not affect assertions.

``` php
$user = factory(User::class)->create([
    'id' => 123,
    'name' => 'Peter',
    'email' => 'peter@example.com',
]);

Search::fakeRecord($user, [
    'name' => 'John',
]);

$record = User::search()->where('id', 123)->raw()['hits'][0];

$this->assertEquals('Peter', $record['name']); // fails
$this->assertEquals('John', $record['name']); // passes
$this->assertEquals('peter@example.com', $record['email']); // passes

Search::fakeRecord($user, [
    'id' => 123,
    'name' => 'John',
], false);

$record = User::search()->where('id', 123)->raw()['hits'][0];

$this->assertEquals('Peter', $record['name']); // fails
$this->assertEquals('John', $record['name']); // passes
$this->assertTrue(!isset($record['email'])); // passes
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[`Search`]: src/Facades/Search.php