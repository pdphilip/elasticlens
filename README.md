<div align="center">
<img src="https://cdn.snipform.io/pdphilip/elasticlens/elasticlens-banner.svg" alt="ElasticLens for Laravel" />
<p>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pdphilip/elasticlens.svg?style=flat-square)](https://packagist.org/packages/pdphilip/elasticlens)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/elasticlens/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pdphilip/elasticlens/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/pdphilip/elasticlens/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/pdphilip/elasticlens/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](http://img.shields.io/packagist/dt/pdphilip/elasticlens.svg)](https://packagist.org/packages/pdphilip/elasticlens)

</p>

<h3>Search your <strong>Laravel models</strong> with Eloquent ease and Elasticsearch power</h3>
<p>Scout's simplicity • Elasticsearch's power • Your rules</p>

</div>

```php
// Add a trait. Search your models.
User::search('mass donuts');
```

```php
// Phrase match + filters + embedded fields + pagination. One query.
User::viaIndex()
    ->searchPhrase('mass donuts')
    ->where('status', 'active')
    ->where('logs.country', 'Norway')
    ->orderByDesc('created_at')
    ->paginate(10);
```

```php
// Find every user within 5km who mentioned "espressos" in their profile.
// Sorted by distance. Because priorities.
User::viaIndex()
    ->searchTerm('espressos')
    ->whereGeoDistance('home.location', '5km', [40.7128, -74.0060])
    ->orderByGeo('home.location', [40.7128, -74.0060])
    ->get();
```

Scout gives you a search box behind a black box. ElasticLens gives you a search engine you can open up.

Every index is a real Eloquent model you own. You define the field mappings. You define the schema. You see exactly what's indexed and how. No magic, no guessing, no driver abstractions between you and your data.

Powered by [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch).

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

Creates `IndexedUser`: a real Elasticsearch model that stays synced with your `User` via observers. Every create, update, delete is reflected automatically.

**3. Search**

```php
// Quick search across all fields
User::search('vinyl collecting');

// Full Elasticsearch query builder. Go nuts.
User::viaIndex()->searchTerm('vinyl')->where('state', 'active')->get();
User::viaIndex()->searchFuzzy('elsticsearsh')->get();   // can't even spell it? no problem
User::viaIndex()->whereRegex('hobby', 'sw(im|itch)')->paginate(10);
```

---

## Embed Relationships

Here's where the "oh cool" becomes "holy shit."

You've got a User model. Profiles in one table. Company in another. Logs in a third. Country in a fourth. In SQL, searching across all of that is a JOIN nightmare you pretend doesn't bother you. With ElasticLens, you flatten everything into
one searchable document:

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

            // Embed the user's profiles as nested objects
            $field->embedsMany('profiles', Profile::class)->embedMap(function ($field) {
                $field->text('bio');
                $field->array('tags');
            });

            // Embed the company they belong to
            $field->embedsBelongTo('company', Company::class)->embedMap(function ($field) {
                $field->text('name');
                $field->text('industry');
            });

            // Last 10 logs only. We're not animals.
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

Now search across all of it:

```php
// Active users at tech companies whose profiles mention "elasticsearch"
User::viaIndex()
    ->where('state', 'active')
    ->where('company.industry', 'Technology')
    ->where('profiles.bio', 'like', '%elasticsearch%')
    ->get();
```

Six SQL tables. Zero JOINs. One query.

Update a `Profile`? The parent `IndexedUser` rebuilds automatically. The observer chain traces all the way up. You don't have to think about it.

---

## Conditional Indexing

Not everything deserves an index entry:

```php
class User extends Model
{
    use Indexable;

    public function excludeIndex(): bool
    {
        return $this->is_banned; // bye
    }
}
```

Excluded records are tracked as skipped (not failed) in build state and health checks.

---

## Index Migrations

Define your Elasticsearch mapping with a Blueprint. Same idea as database migrations:

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
php artisan lens:status              # Bird's eye view of all indexes
php artisan lens:health User         # Deep health check for one index
php artisan lens:build User          # Bulk rebuild all records
php artisan lens:migrate User        # Drop, migrate, rebuild. The nuclear option.
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

Configure globally or per-model whether soft-deleted records keep their index:

```php
// config/elasticlens.php
'index_soft_deletes' => true,
```

```php
// Or per index model
class IndexedUser extends IndexModel
{
    protected ?bool $indexSoftDeletes = true;
}
```

Restoring a model rebuilds its index automatically.

---

## Requirements

|               | Version      |
|---------------|--------------|
| PHP           | 8.2+         |
| Laravel       | 10 / 11 / 12 |
| Elasticsearch | 8.x          |

## Installation

```bash
composer require pdphilip/elasticlens
```

```bash
php artisan lens:install    # Publish config
php artisan migrate         # Create build state + migration log indexes
```

> Requires a configured [Laravel-Elasticsearch](https://github.com/pdphilip/laravel-elasticsearch) connection. [Setup guide ->](https://elasticsearch.pdphilip.com/getting-started/configuration)

---

## Documentation

Full docs at **[elasticlens.pdphilip.com](https://elasticlens.pdphilip.com)**

---

## Credits

- [David Philip](https://github.com/pdphilip)

## License

The MIT License (MIT). See [License File](LICENSE.md).
