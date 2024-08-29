 <div align="center">
  <img
      src="https://cdn.snipform.io/pdphilip/elasticlens/logo.png"
      alt="ElasticLens"
    />

  <p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pdphilip/elasticlens.svg?style=flat-square)](https://packagist.org/packages/pdphilip/elasticlens) [![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/elasticlens/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pdphilip/elasticlens/actions?query=workflow%3Arun-tests+branch%3Amain) [![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/elasticlens/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pdphilip/elasticlens/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain) [![Total Downloads](https://img.shields.io/packagist/dt/pdphilip/elasticlens.svg?style=flat-square)](https://packagist.org/packages/pdphilip/elasticlens)

  </p>
    <h3>
    Search your <strong>Laravel Models</strong> with the convenience of Eloquent and the power of Elasticsearch
  </h3>
  <p>
ElasticLens for Laravel uses Elasticsearch to create and sync a searchable index of your Eloquent models.
  </p>
</div>
<div align="center">
  <img
      src="https://cdn.snipform.io/pdphilip/elasticlens/elasticlens-build.gif"
      alt="ElasticLens Build"
    />
</div>

```php
User::viaIndex()->phrase('loves dogs')->where('status','active')->search();
```

## 🚧 Alpha Notice

ElasticLens is currently in alpha and under active development. Please wait for the first stable release before using it in production.

--- 

### Wait, isn't this what Laravel Scout does?

Yes, but mostly no.

ElasticLens is built from the ground up to fully leverage Elasticsearch's capabilities. It integrates directly with the  [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) package, creating a dedicated `Index Model`
that is automatically synced with your `Base Model`.

The `Index Model` acts as a separate Elasticsearch model managed by ElasticLens, yet you retain full control over it, just like any other Laravel model. In addition to working directly with the `Index Model`, ElasticLens offers tools for
mapping fields (with embedding relationships) during the build process, and managing index migrations.

For Example, a base `User` Model will sync with an Elasticsearch `IndexedUser` Model that provides all the features from [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) to search your `Base Model`.

# Features

- [Zero config setup](#step-1-zero-config-setup): Start indexing with minimal configuration.
- [Eloquent-Like Querying](#step-2-search-your-models): Search your models as if you're using Eloquent, with the full power of Elasticsearch.
- [Custom Field Mapping](#step-3-create-a-field-map): Control how your index is built, including [mapping model relationships as embedded fields](#step-4-update-fieldmap-to-include-relationships-as-embedded-fields).
- [Control Observed models](#step-5-fine-tune-the-observers): Tailor which models are observed for changes.
- [Manage Elasticsearch Migrations](#step-6-define-your-index-models-migrationmap): Define a required blueprint for your index migrations.
- [Comprehensive CLI Tools](#step-7-monitor-and-administer-all-your-indexes-with-artisan-commands): Manage index health, migrate/rebuild indexes, and more with Artisan commands.
- [Built-in IndexableBuildState model](#step-8-optionally-access-the-built-in-indexablebuildstate-model-to-track-index-build-states): Track the build states of your indexes.

# Requirements

- Laravel 10.x & 11.x
- Elasticsearch 8.x

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

1. Add the Indexable Trait to Your Base Model:

Include the `Indexable` trait in your base model to enable automatic indexing.

```php
use PDPhilip\ElasticLens\Indexable;

class User extends Eloquent implements Authenticatable, CanResetPassword
{
    use Indexable;
```

2. Create an Index Model for Your Base Model:

Define a corresponding Index Model that extends `IndexModel`. This model will sync and manage the Elasticsearch index for your `Base Model`.

By default, ElasticLens expects the `Index Model` to be named as `Indexed` + `BaseModelName` and located in the `App\Models\Indexes` directory. For example:

`App\Models\Indexes\IndexedUser.php`

```php
namespace App\Models\Indexes;

use PDPhilip\ElasticLens\IndexModel;

class IndexedUser extends IndexModel{}
```

That's it! Your User model will now automatically sync with the IndexedUser model whenever changes occur. You can search your User model effortlessly, like:

```php
User::viaIndex()->term('running')->orTerm('swimming')->search();
```

---

## Step 2: Search your models

Perform quick and easy full-text searches:

```php
User::search('loves espressos');
```
> Search for the phrase `loves espressos` across all fields and return the base User models

Cute. But that's not why we're here...

To truly harness the power of [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) for eloquent-like querying, you can use more advanced queries:

```php
BaseModel::viaIndex()->{build your ES Eloquent query}->first();
BaseModel::viaIndex()->{build your ES Eloquent query}->get();
BaseModel::viaIndex()->{build your ES Eloquent query}->search();
BaseModel::viaIndex()->{build your ES Eloquent query}->avg();
BaseModel::viaIndex()->{build your ES Eloquent query}->distinct();
BaseModel::viaIndex()->{build your ES Eloquent query}->{etc}
```

### Examples:

#### 1. Basic Term Search:

```php
User::viaIndex()->term('pizza')->orderByDesc('created_at')->limit(3)->search();
```

> This searches all fields for the term 'pizza' and returns the 3 newest results.
> - https://elasticsearch.pdphilip.com/full-text-search#term-search-term

#### 2. Phrase Search:

```php
User::viaIndex()->phrase('Ice bathing')->orderByDesc('created_at')->limit(3)->search();
```
> Searches all fields for the phrase 'Ice bathing' and returns the 3 newest results. Phrases match exact words in order.
> - https://elasticsearch.pdphilip.com/full-text-search#phrase-search-phrase

####  3. Boosting Terms and Minimum Score:

```php
User::viaIndex()->term('David')->field('first_name', 3)->field('last_name', 2)->field('bio')->minScore(2.1)->search();
```

> Searches for the term 'David', boosts the first_name field by 3, last_name by 2, and also checks the bio field. Returns results with a minimum score of 2.1, ordered by the highest score.
> - https://elasticsearch.pdphilip.com/full-text-search#boosting-terms
> - https://elasticsearch.pdphilip.com/full-text-search#minimum-score

#### 4. Geolocation Filtering:
```php
User::viaIndex()->where('status', 'active')
    ->filterGeoPoint('home.location', '5km', [0, 0])
    ->orderByGeo('home.location',[0, 0])
    ->get();
```

> Finds all active users within a 5km radius from the coordinates [0, 0], ordering them from closest to farthest.
> - https://elasticsearch.pdphilip.com/es-specific#geo-point
> - https://elasticsearch.pdphilip.com/ordering-and-pagination#order-by-geo-distance

#### 5. Regex Search:

```php
User::viaIndex()->whereRegex('favourite_color', 'bl(ue)?(ack)?')->get();
```

> Finds all users whose favourite color is blue or black.
> - https://elasticsearch.pdphilip.com/full-text-search#regular-expressions

#### 6. Pagination:

```php
User::viaIndex()->whereRegex('favorite_color', 'bl(ue)?(ack)?')->paginate(10);
```
> Paginate search results.
> - https://elasticsearch.pdphilip.com/ordering-and-pagination

#### 7. Nested Object Search:
```php
User::viaIndex()->whereNestedObject('user_logs', function (Builder $query) {
    $query->where('user_logs.country', 'Norway')->where('user_logs.created_at', '>=',Carbon::now()->modify('-1 week'));
})->get();
```
> Searches nested user_logs for users who logged in from Norway within the last week. Whoa.
> - https://elasticsearch.pdphilip.com/nested-queries

#### 8. Fuzzy Search:

```php
User::viaIndex()->fuzzyTerm('quikc')->orFuzzyTerm('brwn')->andFuzzyTerm('foks')->search();
```
> No spell, no problem. Search Fuzzy.
> - https://elasticsearch.pdphilip.com/full-text-search

#### 9. Highlighting Search Results:
```php
User::viaIndex()->term('espresso')->highlight()->search();

```
> Searches for 'espresso' across all fields and highlights where it was found.
> - https://elasticsearch.pdphilip.com/full-text-search#highlighting


### Note on `Index Model` vs `Base Model` Results

Since the `viaIndex()` taps into the `IndexModel`, the results returned will be instances of `IndexedUser`, not the base `User` model. This can be useful for display purposes, such as highlighting embedded fields.

###  However, in most cases you'll need to return and work with the `Base Model`
To get the results as base models simply chain `->asModel()` at the end of your query:


```php
User::viaIndex()->term('david')->orderByDesc('created_at')->limit(3)->search()->asModel();
User::viaIndex()->whereRegex('favorite_color', 'bl(ue)?(ack)?')->get()->asModel();
User::viaIndex()->whereRegex('favorite_color', 'bl(ue)?(ack)?')->first()->asModel();
```

### For Pagination: `paginateModels()`

- Direct Pagination (no paginator):
```php
User::viaIndex()->whereRegex('favorite_color', 'bl(ue)?(ack)?')->paginate(10)->asModel();
```
This will return the 10 results as models but without a paginator.

- Paginate and Return Base Models use `paginateModels()`:

```php
User::viaIndex()->whereRegex('favorite_color', 'bl(ue)?(ack)?')->paginateModels(10);
```

This will paginate the results from Elasticsearch and return the original base models.


---

## Step 3: Create a field Map

You can define the `fieldMap()` method in your `Index Model` to control how the index is built during synchronization.

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

### Notes:

- The `IndexedUser` records will contain only the fields defined in the `fieldMap()`. The value of `$user->id` will correspond to `$indexedUser->_id`.
- Fields can also be derived from attributes in the `Base Model`. For example, `$field->bool('is_active')` could be derived from a custom attribute in the `Base Model`:
  ```php
    public function getIsActiveAttribute(): bool
    {
        return $this->updated_at >= Carbon::now()->modify('-30 days');
    }
  ```
- When mapping enums, ensure that you also cast them in the `Index Model`.
- If a value is not found during the build process, it will be stored as `null`.

---

## Step 4: Update `fieldMap()` to Include Relationships as Embedded Fields

You can further customize the indexing process by embedding relationships as nested objects within your Index Model. The builder allows you to define fields and embed relationships, enabling more complex data structures in your
Elasticsearch index.

### Examples:

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

4. If a `User` belongs to a `Country` and you don't need to observe the `Country` model:

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

5. If a `User`  has Many `UserLogs` and you only want to embed the last 10:

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

### `IndexField $field` Methods:

- `text($field)`
- `integer($field)`
- `array($field)`
- `bool($field)`
- `type($field, $type)` - Set own type (like Enums)
- `embedsMany($field, $relatedModelClass, $whereRelatedField, $equalsLocalField, $query)`
- `embedsBelongTo($field, $relatedModelClass, $whereRelatedField, $equalsLocalField, $query)`
- `embedsOne($field, $relatedModelClass, $whereRelatedField, $equalsLocalField, $query)`

**Note**: For embeds the `$whereRelatedField`, `$equalsLocalField`, `$query` parameters are optional.

- `$whereRelatedField` is the `foreignKey` & `$equalsLocalField` is the `localKey` and they will be inferred from the relationship if not provided.
- `$query` is a closure that allows you to customize the query for the related model.

### Embedded Relationship Builder Methods:

- `embedMap(function (IndexField $field) {})` - Define the mapping for the embedded relationship
- `dontObserve()` - Don't observe changes in the `$relatedModelClass`

---

## Step 5: Fine-tune the Observers

By default, the base model will be observed for changes (saves) and deletions. When the `Base Model` is deleted, the corresponding `Index Model` will also be deleted, even in cases of soft deletion.

### Handling Embedded Models

When you define a `fieldMap()` with embedded fields, the related models are also observed. For example:

- A save or delete action on `ProfileStatus` will trigger a chain reaction, fetching the related `Profile` and then `User`, which in turn initiates a rebuild of the index for that user record.

However, to ensure these observers are loaded, you need to reference the User model explicitly:

```php
//This alone will not trigger a rebuild
$profileStatus->status = 'Unavailable';
$profileStatus->save();

//This will 
new User::class
$profileStatus->status = 'Unavailable';
$profileStatus->save();
```

### Customizing Observers

If you want ElasticLens to observe `ProfileStatus` without requiring a reference to `User`, follow these steps:

1. Add the `HasWatcher` Trait to `ProfileStatus`:

```php
use PDPhilip\ElasticLens\HasWatcher;

class ProfileStatus extends Eloquent
{
    use HasWatcher;
```

2. Define the Watcher in the `elasticlens.php` Config File:

```php
'watchers' => [
    \App\Models\ProfileStatus::class => [
        \App\Models\Indexes\IndexedUser::class,
    ],
],
```

### Disabling Base Model Observation

If you want to disable the automatic observation of the `Base Model`, include the following in your `Index Model`:

```php
class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;
    
    protected $observeBase = false;

```

---

## Step 6: Define your `Index Model`'s `migrationMap()`

Elasticsearch automatically indexes new fields it encounters, but it might not always index them in the way you need. To ensure the index is structured correctly, you can define a `migrationMap()` in your Index Model.

Since the `Index Model` utilizes the [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) package, you can use `IndexBlueprint` to customize your `migrationMap()`

```php
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;

class IndexedUser extends IndexModel
{
    //......
    public function migrationMap(): callable
    {
        return function (IndexBlueprint $index) {
            $index->text('name');
            $index->keyword('first_name');
            $index->text('first_name');
            $index->keyword('last_name');
            $index->text('last_name');
            $index->keyword('email');
            $index->text('email');
            $index->text('avatar')->index(false);
            $index->keyword('type');
            $index->text('type');
            $index->keyword('state');
            $index->text('state');
            //...etc
        };
    }
```

### Notes:

- **Documentation**: For more details on migrations, refer to the: https://elasticsearch.pdphilip.com/migrations
- **Running the Migration**: To execute the migration and rebuild all your indexed, use the following command:

```bash
php artisan lens:migrate User
```

This command will delete the existing index, run the migration, and rebuild all records.

---

## Step 7: Monitor and administer all your indexes with Artisan commands

Use the following Artisan commands to manage and monitor your Elasticsearch indexes:

1. Check Overall Status:

```bash
php artisan lens:status 
```

Displays the overall status of all your indexes and the ElasticLens configuration.

2. Check Index Health:

```bash
php artisan lens:health User
```

Provides a comprehensive state of a specific index, in this case, for the `User` model.

3. Migrate and Build/Rebuild an Index:

```bash
php artisan lens:build User
```

Deletes the existing User index, runs the migration, and rebuilds all records.

4. Create a New `Index Model` for a `Base Model`:

```bash
php artisan lens:make Company
```

Generates a new index for the `Company` model.

---

## Step 8: Optionally access the built-in `IndexableBuildState` model to track index build states

ElasticLens includes a built-in `IndexableBuildState` model that allows you to monitor and track the state of your index builds. This model records the status of each index build, providing you with insights into the indexing process.

### Model Fields:

- string `$model`: The base model being indexed.
- string `$model_id`: The ID of the base model.
- string `$index_model`: The corresponding index model.
- string `$last_source`: The last source of the build state.
- IndexableStateType `$state`: The current state of the index build.
- array `$state_data`: Additional data related to the build state.
- array `$logs`: Logs of the indexing process.
- Carbon `$created_at`: Timestamp of when the build state was created.
- Carbon `$updated_at`: Timestamp of the last update to the build state.

### Attributes:

- @property-read string `$state_name`: The name of the current state.
- @property-read string `$state_color`: The color associated with the current state.

Built-in methods include:

```php
IndexableBuildState::returnState($model, $modelId, $indexModel);
IndexableBuildState::countModelErrors($indexModel);
IndexableBuildState::countModelRecords($indexModel);
```

**Note**: While you can query the `IndexableBuildState` model directly, avoid writing or deleting records within it manually, as this can interfere with the health checks and overall integrity of the indexing process. The model should be
used for reading purposes only to ensure accurate monitoring and reporting.

## Credits

- [David Philip](https://github.com/pdphilip)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
