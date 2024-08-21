<div align="center">
  <img
      src="https://cdn.snipform.io/pdphilip/elasticlens/logo.png"
      alt="ElasticLens"
      height="256"
    />
  <h4>
    Search your <strong>Laravel Models</strong> with the convenience of Eloquent and the power of Elasticsearch
  </h4>
  <p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pdphilip/elasticlens.svg?style=flat-square)](https://packagist.org/packages/pdphilip/elasticlens) [![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/elasticlens/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pdphilip/elasticlens/actions?query=workflow%3Arun-tests+branch%3Amain) [![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/elasticlens/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pdphilip/elasticlens/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain) [![Total Downloads](https://img.shields.io/packagist/dt/pdphilip/elasticlens.svg?style=flat-square)](https://packagist.org/packages/pdphilip/elasticlens)

  </p>
  <p>
ElasticLens for Laravel uses Elasticsearch to create and sync a searchable index of your Eloquent models.
  </p>
</div>
<div align="center">
  <img
      src="https://cdn.snipform.io/pdphilip/elasticlens/lens-build.gif"
      alt="ElasticLens Build"
    />
</div>

```php
User::viaIndex()->phrase('loves dogs')->where('age', '>=', 18)->where('status','active')->search();
```

--- 

### Wait, doesn't Laravel Scout already do this?

Yes, but mostly no.

ElasticLens is built from the ground up around Elasticsearch and plugs directly into the [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) package to create and sync a separate `Index Model`.
The `Index Model` is synced with your `Base Model` and is available in your codebase to be accessed and manipulated directly giving you full control.

For Example, a base `User` model will sync with an `IndexedUser` model that allows you to use all the features from [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) to search your `Base Model`.

# Features

- [Zero config setup](#step-1-zero-config-setup)
- [Search your models](#step-2-search-your-models) with the convenience of Eloquent and all the power of Elasticsearch
- [Index field mapping](#step-3-create-a-field-map) to control how the index is built
- [Mapping model relationships as embedded fields](#step-4-update-fieldmap-to-include-relationships-as-embedded-fields)
- [Control Observed models](#step-5-fine-tune-the-observers)
- [Manage Elasticsearch Migrations](#step-6-define-your-index-models-migrationmap)
- [Artisan commands](#step-7-monitor-and-administer-all-your-indexes-with-artisan-commands) for Overall Status, Index Health check, Migrating/(Re)building Indexes, Making IndexedModel.
- [Built-in IndexableState model](#step-8-optionally-use-the-built-in-indexablestate-model-to-track-the-build-states) that tracks and logs the build states of the indexes

# Installation

You can install the package via composer:

```bash
composer require pdphilip/elasticlens
```

Publish config file and run the migrations with:

```bash
php artisan lens:install
```

# Usage (Walkthrough)

## Step 1: Zero config setup

1. Add the `indexable` trait to your base model, ex:

```php
use PDPhilip\ElasticLens\Indexable;

class User extends Eloquent implements Authenticatable, CanResetPassword
{
    use Indexable;
```

2. Create an Index Model for your base model, ex:

`App\Models\Indexes\IndexedUser.php`

```php
namespace App\Models\Indexes;

use PDPhilip\ElasticLens\IndexModel;

class IndexedUser extends IndexModel{}
```

That's it! Your User model will now be observed for changes and synced with the IndexedUser model. You can now search your User model like:

```php
User::viaIndex()->term('running')->orTerm('swimming')->search();
```

---

## Step 2: Search your models

---

## Step 3: Create a field Map

You can define `fieldMap()` in your Index Model to control how the index is built on sync.

```php
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Builder\IndexField;

class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;
    
    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map(User::class, function (IndexField $field) {
            $field->text('first_name');
            $field->text('last_name');
            $field->text('email');
            $field->bool('is_active');
            $field->type('state', UserState::class); //Maps enum
            $field->text('created_at');
            $field->text('updated_at');
        });
    }
```

Notes:

- The `IndexedUser` records will only have those fields, and the value of `$user->id` will be the same as `$indexedUser->_id`
- The fields can also be attributes from the Base Model, ex `$field->bool('is_active')` could be derived from the base model's attribute
  ```php
    public function getIsActiveAttribute(): bool
    {
        return $this->updated_at >= Carbon::now()->modify('-30 days');
    }
  ```
- If you're mapping enums, then you will also need to cast them in `Index Model`
- If a value is not found on the build process, it will be stored as `null`

---

## Step 4: Update `fieldMap()` to include relationships as embedded fields

You can define the field mapping in your Index Model to control how the index is built. The builder allows you to define the fields and embed relationships as nested objects.

1. If a `User` has many `Profiles`

```php
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Builder\IndexField;

class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;
    
    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map(User::class, function (IndexField $field) {
            $field->text('first_name');
            $field->text('last_name');
            $field->text('email');
            $field->bool('is_active');
            $field->type('type', UserType::class);
            $field->type('state', UserState::class);
            $field->text('created_at');
            $field->text('updated_at');
            $field->embedsMany('profiles', Profile::class)->embedMap(function (IndexField $field) {
                $field->text('profile_name');
                $field->text('about');
                $field->array('profile_tags');
            });
        });
    }
```

2. If a `Profile` has one `ProfileStatus`

```php
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Builder\IndexField;

class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;
    
    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map(User::class, function (IndexField $field) {
            $field->text('first_name');
            $field->text('last_name');
            $field->text('email');
            $field->bool('is_active');
            $field->type('type', UserType::class);
            $field->type('state', UserState::class);
            $field->text('created_at');
            $field->text('updated_at');
            $field->embedsMany('profiles', Profile::class)->embedMap(function (IndexField $field) {
                $field->text('profile_name');
                $field->text('about');
                $field->array('profile_tags');
                $field->embedsOne('status', ProfileStatus::class)->embedMap(function (IndexField $field) {
                    $field->text('id');
                    $field->text('status');
                });
            });
        });
    }
```

3. If a `User` belongs to an `Account`

```php
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Builder\IndexField;

class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;
    
    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map(User::class, function (IndexField $field) {
            $field->text('first_name');
            $field->text('last_name');
            $field->text('email');
            $field->bool('is_active');
            $field->type('type', UserType::class);
            $field->type('state', UserState::class);
            $field->text('created_at');
            $field->text('updated_at');
            $field->embedsMany('profiles', Profile::class)->embedMap(function (IndexField $field) {
                $field->text('profile_name');
                $field->text('about');
                $field->array('profile_tags');
                $field->embedsOne('status', ProfileStatus::class)->embedMap(function (IndexField $field) {
                    $field->text('id');
                    $field->text('status');
                });
            });
            $field->embedsBelongTo('account', Account::class)->embedMap(function (IndexField $field) {
                $field->text('name');
                $field->text('url');
            });
        });
    }
```

4. If a `User`  belongs to a `Country` and we don't need to observe the `Country` Model

```php
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Builder\IndexField;

class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;
    
    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map(User::class, function (IndexField $field) {
            $field->text('first_name');
            $field->text('last_name');
            $field->text('email');
            $field->bool('is_active');
            $field->type('type', UserType::class);
            $field->type('state', UserState::class);
            $field->text('created_at');
            $field->text('updated_at');
            $field->embedsMany('profiles', Profile::class)->embedMap(function (IndexField $field) {
                $field->text('profile_name');
                $field->text('about');
                $field->array('profile_tags');
                $field->embedsOne('status', ProfileStatus::class)->embedMap(function (IndexField $field) {
                    $field->text('id');
                    $field->text('status');
                });
            });
            $field->embedsBelongTo('account', Account::class)->embedMap(function (IndexField $field) {
                $field->text('name');
                $field->text('url');
            });
            $field->embedsBelongTo('country', Country::class)->embedMap(function (IndexField $field) {
                $field->text('country_code');
                $field->text('name');
                $field->text('currency');
            })->dontObserve();  // Don't observe changes in the country model
        });
    }
```

5. If a `User`  has Many `UserLog`s and we only want to embed the last 10

```php
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Builder\IndexField;

class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;
    
    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map(User::class, function (IndexField $field) {
            $field->text('first_name');
            $field->text('last_name');
            $field->text('email');
            $field->bool('is_active');
            $field->type('type', UserType::class);
            $field->type('state', UserState::class);
            $field->text('created_at');
            $field->text('updated_at');
            $field->embedsMany('profiles', Profile::class)->embedMap(function (IndexField $field) {
                $field->text('profile_name');
                $field->text('about');
                $field->array('profile_tags');
                $field->embedsOne('status', ProfileStatus::class)->embedMap(function (IndexField $field) {
                    $field->text('id');
                    $field->text('status');
                });
            });
            $field->embedsBelongTo('account', Account::class)->embedMap(function (IndexField $field) {
                $field->text('name');
                $field->text('url');
            });
            $field->embedsBelongTo('country', Country::class)->embedMap(function (IndexField $field) {
                $field->text('country_code');
                $field->text('name');
                $field->text('currency');
            })->dontObserve();  // Don't observe changes in the country model
            $field->embedsMany('logs', UserLog::class, null, null, function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10); // Limit the logs to the 10 most recent
            })->embedMap(function (IndexField $field) {
                $field->text('title');
                $field->text('ip');
                $field->array('log_data');
            });
        });
    }
```

### `IndexField $field` has the following methods:

- `text($field)`
- `integer($field)`
- `array($field)`
- `bool($field)`
- `type($field, $type)` - Set own type (like Enums)
- `embedsMany($field, $relation, $whereRelatedField = null, $equalsLocalField = null, $query = null)`
- `embedsBelongTo($field, $relation, $whereRelatedField = null, $equalsLocalField = null, $query = null)`
- `embedsOne($field, $relation, $whereRelatedField = null, $equalsLocalField = null, $query = null)`

### An Embedded relationship initiates a new builder instance with the following methods:

- `embedMap(function (IndexField $field) {})` - Define the mapping for the embedded relationship
- `dontObserve()` - Don't observe changes in the related model

---

## Step 5: Fine-tune the observers

Without any setup, the base model will be observed for changes (saved) and deletions. When the Base Model is deleted the corresponding Index model will also be deleted. Even if soft deleted.

When you define a  `fieldMap()` with embedded fields, the embedded Models will also be observed, For example:

- A save or delete on `ProfileStatus` will chain to `Profile` and then to the `User` to initiate a rebuild

However, the `User` model will need to be called for these observers to load, so

```php
//This alone will not trigger a rebuild
$profileStatus->status = 'Unavailable';
$profileStatus->save();

//This will 
new User::class
$profileStatus->status = 'Unavailable';
$profileStatus->save();
```

If you want to tell ElasticLens to watch `ProfileStatus` without having to call the `User::class` then:

1. Add a trait in `ProfileStatus`

```php
use PDPhilip\ElasticLens\hasWatcher;

class ProfileStatus extends Eloquent
{
    use hasWatcher;
```

2. Define the watcher in the `elasticlens.php` config file

```php
'watchers' => [
    \App\Models\ProfileStatus::class => [
        \App\Models\Indexes\IndexedUser::class,
    ],
],
```

If you would like to disable observing the `Base Model`, then in your `Index Model`, include:

```php
class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;
    
    protected $observeBase = false;

```

---

## Step 6: Define your `Index Model`'s `migrationMap()`

Elasticsearch will automatically index new fields that it finds, however, it will guess and may not index them how you need them.

Since the `Index Model` uses the [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) package, you can use the `IndexBlueprint` to build your desired `migrationMap()`

```php
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;

class IndexedUser extends IndexModel
{
    //......
    public function migrationMap(): array
    {
        return [
            'version'   => 1,
            'blueprint' => function (IndexBlueprint $index) {
                $index->keyword('first_name');
                $index->text('first_name');
                $index->keyword('last_name');
                $index->text('last_name');
                $index->text('name');
                $index->keyword('email');
                $index->text('email');
                //etc
            },
        ];
    }
```

Docs for Migrations: https://elasticsearch.pdphilip.com/migrations

The version is captured in the `IndexableState` model when built

To run the migration: `php artisan lens:build User` - which will delete the entire index, run the migration and rebuild all the records


---

## Step 7: Monitor and administer all your indexes with Artisan commands

1. Overall Status: `php artisan lens:status`
2. Index Health:  `php artisan lens:health User`
3. Build/Rebuild: `php artisan lens:build User`
4. Make new Index: `php artisan lens:make Company`

---

## Step 8: Optionally use the built-in `IndexableState` model to track the build states

## Credits

- [David Philip](https://github.com/pdphilip)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
