<div align="center">
<img src="https://cdn.snipform.io/pdphilip/elasticlens/elasticlens-banner.svg" alt="ElasticLens for Laravel" />
  <p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pdphilip/elasticlens.svg?style=flat-square)](https://packagist.org/packages/pdphilip/elasticlens)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/elasticlens/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pdphilip/elasticlens/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/elasticlens/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pdphilip/elasticlens/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](http://img.shields.io/packagist/dt/pdphilip/elasticlens.svg)](https://packagist.org/packages/pdphilip/elasticlens)

  </p>
</div>

The convenience of Scout. The full power of Elasticsearch. Complete control of your models.

```php
// Starts just like Scout - add a trait, search your models.
User::search('loves espressos');
```

```php
// Except you're not limited to basic text search.
User::viaIndex()
    ->searchPhrase('ice bathing')
    ->where('status', 'active')
    ->whereNestedObject('logs', function ($query) {
        $query->where('logs.country', 'Norway');
    })
    ->orderByDesc('created_at')
    ->paginate(10);
```

```php
// Geo queries. Fuzzy matching. Regex. Aggregations. On your Laravel models.
User::viaIndex()
    ->filterGeoPoint('home.location', '5km', [40.7128, -74.0060])
    ->orderByGeo('home.location', [40.7128, -74.0060])
    ->get();
```

Scout gives you a search box behind a black box. ElasticLens gives you a search engine you can open up.

Every index is a real Eloquent model you can query, inspect, and control directly. You define the field mappings. You define the Elasticsearch schema. You see exactly what's indexed and how. No magic, no guessing, no driver abstractions between you and your data.

Under the hood: a dedicated Elasticsearch index per model with embedded relationships, migrations, and auto-sync via observers - all powered by [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch).

---

## How It Works

**1. Add the trait**

```php
class User extends Model
{
    use Indexable;
}
```

**2. Generate the index model**

```bash
php artisan lens:make User
```

This creates `IndexedUser` - a real Elasticsearch model that stays synced with your `User` model via observers. Every create, update, and delete is automatically reflected.

**3. Search**

```php
// Quick search - returns User models
User::search('david');

// Full power - term search, fuzzy, phrase, regex, geo, nested, aggregations
User::viaIndex()->searchTerm('david')->where('state', 'active')->getBase();
User::viaIndex()->searchFuzzy('quikc brwn foks')->get();
User::viaIndex()->whereRegex('favorite_color', 'bl(ue)?(ack)?')->paginateBase(10);
```

---

## Embed Relationships Into Your Index

This is where it gets interesting. Flatten your relational data into Elasticsearch and search across it as nested objects.

```php
class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;

    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map(User::class, function (IndexField $field) {
            $field->text('first_name');
            $field->text('last_name');
            $field->text('email');
            $field->type('state', UserState::class);
            $field->embedsMany('profiles', Profile::class)->embedMap(function ($field) {
                $field->text('profile_name');
                $field->text('about');
                $field->array('tags');
            });
            $field->embedsBelongTo('company', Company::class)->embedMap(function ($field) {
                $field->text('name');
                $field->text('industry');
            });
            $field->embedsMany('logs', UserLog::class, null, null, function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            })->embedMap(function ($field) {
                $field->text('action');
                $field->text('ip');
            });
        });
    }
}
```

Then search across all of it:

```php
User::viaIndex()->whereNestedObject('profiles', function ($query) {
    $query->where('profiles.about', 'like', '%elasticsearch%');
})->get();
```

The related models are observed too. Update a `Profile` and the parent `IndexedUser` rebuilds automatically.

---

## Conditional Indexing

Control which records get indexed:

```php
class User extends Model
{
    use Indexable;

    public function excludeIndex(): bool
    {
        return $this->is_admin;
    }
}
```

Excluded models are skipped during indexing, stale records are cleaned up, and health checks account for the difference.

---

## Index Migrations

Define your Elasticsearch mapping with a Blueprint, just like database migrations:

```php
public function migrationMap(): callable
{
    return function (Blueprint $index) {
        $index->text('first_name');
        $index->keyword('first_name');
        $index->text('email');
        $index->keyword('email');
        $index->keyword('state');
        $index->nested('profiles');
    };
}
```

```bash
php artisan lens:migrate User
```

<div align="center">
  <img src="https://cdn.snipform.io/pdphilip/elasticlens/lens-migrate.gif" alt="ElasticLens Migrate" />
</div>

---

## CLI Tools

```bash
php artisan lens:status              # Overview of all indexes
php artisan lens:health User         # Detailed health check for an index
php artisan lens:build User          # Bulk rebuild all index records
php artisan lens:migrate User        # Drop, migrate, and rebuild
php artisan lens:make Profile        # Generate a new index model
```

<div align="center">
  <img src="https://cdn.snipform.io/pdphilip/elasticlens/lens-status.png" alt="ElasticLens Status" />
</div>

<div align="center">
  <img src="https://cdn.snipform.io/pdphilip/elasticlens/lens-build-v2.gif" alt="ElasticLens Build" />
</div>

---

## Soft Delete Support

ElasticLens respects Laravel's `SoftDeletes`. Configure globally or per-model whether soft-deleted records keep their index:

```php
// config/elasticlens.php
'index_soft_deletes' => true,  // Keep index records for soft-deleted models
```

```php
// Or per index model
class IndexedUser extends IndexModel
{
    protected ?bool $indexSoftDeletes = true;
}
```

Restoring a soft-deleted model automatically rebuilds its index.

---

## Requirements

| | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 10 / 11 / 12 |
| Elasticsearch | 8.x |

## Installation

```bash
composer require pdphilip/elasticlens
```

```bash
php artisan lens:install    # Publish config
php artisan migrate         # Create build state + migration log indexes
```

> Requires [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) connection config. See [setup guide](https://elasticsearch.pdphilip.com/getting-started/configuration).

---

## Documentation

Full documentation at **[elasticsearch.pdphilip.com/elasticlens](https://elasticsearch.pdphilip.com/elasticlens/getting-started/)**

- [Index Models](https://elasticsearch.pdphilip.com/elasticlens/index-model/)
- [Field Mapping](https://elasticsearch.pdphilip.com/elasticlens/field-mapping/)
- [Full-Text Search](https://elasticsearch.pdphilip.com/elasticlens/full-text-search)
- [Migrations](https://elasticsearch.pdphilip.com/elasticlens/index-model-migrations/)
- [Model Observers](https://elasticsearch.pdphilip.com/elasticlens/model-observers/)
- [CLI Tools](https://elasticsearch.pdphilip.com/elasticlens/artisan-cli-tools/)
- [Build States](https://elasticsearch.pdphilip.com/elasticlens/build-migration-states/)

---

## Credits

- [David Philip](https://github.com/pdphilip)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
