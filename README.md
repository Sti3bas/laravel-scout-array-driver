# Array driver for Laravel Scout

This package adds an `array` driver to Laravel Scout and provides custom PHPUnit assertions to make testing search related functionality easier.

## Contents

- [Installation](#installation)
- [Usage](#usage)
- [License](#license)

## Installation

Install the package via Composer:

```bash
composer require sti3bas/laravel-scout-array-driver --dev
```

Set Scout driver to `array` in `.env.testing` file:

```
SCOUT_DRIVER=array
```

or in `phpunit.xml` file:

```xml
<server name="SCOUT_DRIVER" value="array"/>
```

## Usage

The [`Search`] facade provides the following methods and assertions:

### assertContains($model, $callback = null)

Checks if model exists in the search index.

```php
$user = User::factory()->create([
    'name' => 'Oliver',
]);

$user2 = User::withoutSyncingToSearch(function () {
    return User::factory()->create([
        'name' => 'John',
    ]);
});

Search::assertContains($user) // ✅
    ->assertContains($user2) // ❌
    ->assertContains($user, function ($record) { // ✅
        return $record['name'] === 'Oliver';
    })
    ->assertContains($user, function ($record) { // ❌
        return $record['name'] === 'John';
    })
    ->assertContains($user2, function ($record) { // ❌
        return $record['name'] === 'John';
    });
```

### assertNotContains($model, $callback = null)

Checks if model doesn't exist in the search index.

```php
$user = User::factory()->create([
    'name' => 'Oliver',
]);

$user2 = User::withoutSyncingToSearch(function () {
    return User::factory()->create([
        'name' => 'John',
    ]);
});

Search::assertNotContains($user) // ❌
    ->assertNotContains($user2) // ✅
    ->assertNotContains($user, function ($record) { // ❌
        return $record['name'] === 'Oliver';
    })
    ->assertNotContains($user, function ($record) { // ✅
        return $record['name'] === 'John';
    })
    ->assertNotContains($user2, function ($record) { // ✅
        return $record['name'] === 'John';
    });
```

### assertContainsIn($index, $model, $callback = null)

Checks if model exists in custom search index.

```php
$user = User::factory()->create([
    'name' => 'Oliver',
]);

Search::assertContainsIn('users', $user) // ✅
    ->assertContainsIn('non_existing_index', $user) // ❌
    ->assertContainsIn('users', $user, function ($record) { // ✅
        return $record['name'] === 'Oliver';
    })
    ->assertContainsIn('users', $user, function ($record) { // ❌
        return $record['name'] === 'John';
    });
```

### assertNotContainsIn($index, $model, $callback = null)

Checks if model doesn't exist in custom search index.

```php
$user = User::factory()->create([
    'name' => 'Oliver',
]);

Search::assertNotContainsIn('users', $user) // ❌
    ->assertNotContainsIn('non_existing_index', $user) // ✅
    ->assertNotContainsIn('users', $user, function ($record) { // ❌
        return $record['name'] === 'Oliver';
    })
    ->assertNotContainsIn('users', $user, function ($record) { // ✅
        return $record['name'] === 'John';
    });
```

### assertEmpty()

Checks if all search indexes are empty.

```php
Search::assertEmpty(); // ✅

User::factory()->create();

Search::assertEmpty(); // ❌
```

### assertEmptyIn($index)

Checks if search index is empty.

```php
Search::assertEmptyIn('users'); // ✅

User::factory()->create();

Search::assertEmptyIn('users'); // ❌
```

### assertNotEmpty()

Checks if there is at least one record in any of search indexes.

```php
Search::assertNotEmpty(); // ❌

User::factory()->create();

Search::assertNotEmpty(); // ✅
```

### assertNotEmptyIn($index)

Checks if search index is not empty.

```php
Search::assertNotEmptyIn('users'); // ❌

User::factory()->create();

Search::assertNotEmptyIn('users'); // ✅
```

### assertSynced($model, $callback = null)

Checks if model was synced to search index. This assertion checks every record of the given model which was synced during the request.

```php
$user = User::factory()->create([
    'name' => 'Peter',
]);

Search::assertSynced($user); // ✅

$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // ❌
    ->assertSynced($user) // ✅
    ->assertSynced($user, function ($record) { // ✅
        return $record['name'] === 'Peter';
    })
    ->assertSynced($user, function ($record) { // ✅
        return $record['name'] === 'John';
    })
    ->assertSynced($user, function ($record) { // ❌
        return $record['name'] === 'Oliver';
    });
```

### assertNotSynced($model, $callback = null)

Checks if model wasn't synced to search index. This assertion checks every record of the given model which was synced during the request.

```php
$user = User::factory()->create([
    'name' => 'Peter',
]);

Search::assertNotSynced($user); // ❌

$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // ❌
    ->assertNotSynced($user); // ❌

Search::assertNotSynced($user, function ($record) { // ❌
    return $record['name'] === 'Peter';
})
->assertNotSynced($user, function ($record) { // ❌
    return $record['name'] === 'John';
})
->assertNotSynced($user, function ($record) { // ✅
    return $record['name'] === 'Oliver';
});
```

### assertSyncedTo($model, $callback = null)

Checks if model was synced to custom search index. This assertion checks every record of the given model which was synced during the request.

```php
$user = User::factory()->create([
    'name' => 'Peter',
]);

Search::assertSyncedTo('users', $user); // ✅

$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // ❌
    ->assertSyncedTo('users', $user) // ✅
    ->assertSyncedTo('users', $user, function ($record) {
        return $record['name'] === 'Peter'; // ✅
    })
    ->assertSyncedTo('users', $user, function ($record) {
        return $record['name'] === 'John'; // ✅
    })
    ->assertSyncedTo('non_existing_index', $user, function ($record) {
        return $record['name'] === 'John'; // ❌
    });
```

### assertNotSyncedTo($model, $callback = null)

Checks if model wasn't synced to custom search index. This assertion checks every record of the given model which was synced during the request.

```php
$user = User::factory()->create([
    'name' => 'Peter',
]);

Search::assertNotSyncedTo('users', $user) // ❌
    ->assertNotSyncedTo('not_existing_index', $user); // ✅

$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // ❌
    ->assertNotSyncedTo('users', $user) // ❌
    ->assertNotSyncedTo('users', $user, function ($record) {
        return $record['name'] === 'Peter'; // ❌
    })
    ->assertNotSyncedTo('users', $user, function ($record) {
        return $record['name'] === 'Oliver'; // ✅
    });
```

### assertSyncedTimes($model, $callback = null)

Checks if model was synced expected number of times. This assertion checks every record of the given model which was synced during the request.

```php
$user = User::withoutSyncingToSearch(function () {
    return User::factory()->create([
        'name' => 'Peter',
    ]);
});

Search::assertSyncedTimes($user, 0) // ✅
    ->assertSyncedTimes($user, 1); // ❌

$user->searchable();
$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // ❌
    ->assertSyncedTimes($user, 2) // ✅
    ->assertSyncedTimes($user, 1, function ($record) {
        return $record['name'] === 'Peter'; // ✅
    })
    ->assertSyncedTimes($user, 1, function ($record) {
        return $record['name'] === 'John'; // ✅
    })
    ->assertSyncedTimes($user, 1, function ($record) {
        return $record['name'] === 'Oliver'; // ❌
    });
```

### assertSyncedTimesTo($index, $model, $callback = null)

Checks if model was synced to custom search index expected number of times. This assertion checks every record of the given model which was synced during the request.

```php
$user = User::withoutSyncingToSearch(function () {
    return User::factory()->create([
        'name' => 'Peter',
    ]);
});

Search::assertSyncedTimesTo('users', $user, 0) // ✅
    ->assertSyncedTimesTo('non_existing_index', $user, 1); // ❌

$user->searchable();
$user->update(['name' => 'John']);
$user->delete();

Search::assertContains($user) // ❌
    ->assertSyncedTimesTo('users', $user, 2) // ✅
    ->assertSyncedTimesTo('users', $user, 1, function ($record) {
        return $record['name'] === 'Peter'; // ✅
    })
    ->assertSyncedTimesTo('non_existing_index', 1, function ($record) {
        return $record['name'] === 'John'; // ❌
    });
```

### assertNothingSynced()

Checks if nothing was synced to any of search indexes. This assertion checks every record which was synced during the request.

```php
Search::assertNothingSynced(); // ✅

User::factory()->create();

Search::assertNothingSynced(); // ❌
```

### assertNothingSyncedTo()

Checks if nothing was synced to custom search index. This assertion checks every record which was synced during the request.

```php
Search::assertNothingSyncedTo('users'); // ✅

User::factory()->create();

Search::assertNothingSyncedTo('users'); // ❌
```

### assertIndexExists($index)

Checks if search index exists.

```php
$manager = $this->app->make(EngineManager::class);

$engine = $manager->engine();

Search::assertIndexExists('test'); // ❌

$engine->createIndex('test');

Search::assertIndexExists('test'); // ✅
```

### assertIndexNotExists($index)

Checks if search index doesn't exist.

```php
$manager = $this->app->make(EngineManager::class);

$engine = $manager->engine();

Search::assertIndexNotExists('test'); // ✅

$engine->createIndex('test');

Search::assertIndexNotExists('test'); // ❌
```

### fakeRecord($model, $data, $merge = true, $index = null)

This method allows to fake search index record of the model. It will not affect assertions.

```php
$user = User::factory()->create([
    'id' => 123,
    'name' => 'Peter',
    'email' => 'peter@example.com',
]);

Search::fakeRecord($user, [
    'name' => 'John',
]);

$record = User::search()->where('id', 123)->raw()['hits'][0];

$this->assertEquals('Peter', $record['name']); // ❌
$this->assertEquals('John', $record['name']); // ✅
$this->assertEquals('peter@example.com', $record['email']); // ✅

Search::fakeRecord($user, [
    'id' => 123,
    'name' => 'John',
], false);

$record = User::search()->where('id', 123)->raw()['hits'][0];

$this->assertEquals('Peter', $record['name']); // ❌
$this->assertEquals('John', $record['name']); // ✅
$this->assertTrue(!isset($record['email'])); // ✅
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[`search`]: src/Facades/Search.php
