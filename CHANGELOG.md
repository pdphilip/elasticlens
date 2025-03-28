# Changelog

All notable changes to `elasticlens` will be documented in this file.

## v3.0.1 - 2025-03-28

This release is compatible with Laravel 10, 11 & 12

### What's changed

#### New features

- Skippable models via optional `excludeIndex(): bool` method in your base model
- Delete an index from your model instance: `$user->removeIndex()`
- New Index Model method: `IndexedUser::whereIndexBuilds()->get()` - returns index build logs for model
- New Index Model method: `IndexedUser::whereFailedIndexBuilds()->get()` - returns failed index build logs for model
- New Index Model method: `IndexedUser::whereMigrations()->get()` - returns migration logs for model
- New Index Model method: `IndexedUser::whereMigrationErrors()->get()` - returns failed migrations for model
- New index Model method: `IndexedUser::lensHealth()` - returns an array of the index health

#### Fixes

- v5 compatibility fixes with bulk insert and saving without refresh
- Indexable trait `search()` runs `searchPhrasePrefix()` under the hood
- `paginateBase()` honors current path

**Full Changelog**: https://github.com/pdphilip/elasticlens/compare/v3.0.0...v3.0.1

## v3.0.0 - 2025-03-28

This is an updated dependency release compatible with:

- Laravel 10/11/12
- `laravel-elasticsearch` package v5

### What's Changed

* Bump dependabot/fetch-metadata from 2.2.0 to 2.3.0 by @dependabot in https://github.com/pdphilip/elasticlens/pull/1
* Bump aglipanci/laravel-pint-action from 2.4 to 2.5 by @dependabot in https://github.com/pdphilip/elasticlens/pull/2

### New Contributors

* @dependabot made their first contribution in https://github.com/pdphilip/elasticlens/pull/1

**Full Changelog**: https://github.com/pdphilip/elasticlens/compare/v2.0.1...v3.0.0

## v2.0.1 - 2024-11-04

Bug fix: `lens:make` command fixed to properly accommodate Domain spaced setups

**Full Changelog**: https://github.com/pdphilip/elasticlens/compare/v2.0.0...v2.0.1

## v2.0.0 - 2024-10-21

**Version 2 introduces breaking changes** to support multiple model namespace mappings, providing flexibility for domain-driven architecture. This update allows the use of multiple model sources.

The elasticlens.php config file now requires the following structure:

```php
'namespaces' => [
    'App\Models' => 'App\Models\Indexes',
],

'index_paths' => [
    'app/Models/Indexes/' => 'App\Models\Indexes',
],





```
•	The **namespaces** key maps models to their respective index namespaces.
•	The **index_paths** key maps file paths to the corresponding index namespaces.

**Full Changelog**: https://github.com/pdphilip/elasticlens/compare/v1.3.1...v2.0.0

## v1.3.1 - 2024-10-03

- Bug fix: Bulk insert was not writing to the `IndexableBuild` model correctly
- Better IDE support for IndexModel macros, ie: `getBase()`,  `asBase()` & `paginateBase()`

**Full Changelog**: https://github.com/pdphilip/elasticlens/compare/v1.3.0...v1.3.1

## v1.3.0 - 2024-10-02

### Changes

- Renamed `asModel()` to `asBase()`
- Renamed `paginateModels()` to `paginateBase()`
- Added convenience method `getBase()` that can replace `....->get()->asBase()`

Dependency update to use [laravel-elasticsearch ^v4.4](https://github.com/pdphilip/laravel-elasticsearch/releases/tag/v4.4.0)

**Full Changelog**: https://github.com/pdphilip/elasticlens/compare/v1.2.2...v1.3.0

## v1.2.0 - 2024-09-16

Dependency update to use [laravel-elasticsearch v4.2](https://github.com/pdphilip/laravel-elasticsearch/releases/tag/v4.2.0)

**Full Changelog**: https://github.com/pdphilip/elasticlens/compare/v1.1.0...v1.2.0

## v1.1.0 - 2024-09-09

### New Feature

Bulk index (re)builder with:

```bash
php artisan lens:build {model}








```
<div align="center">
  <img
      src="https://cdn.snipform.io/pdphilip/elasticlens/lens-build-v2.gif"
      alt="ElasticLens Build"
    />
</div>
### Changes
The previous `lens:build` command is now `lens:migrate`, which better describes the feature.
```bash
php artisan lens:migrate {model}
```
<div align="center">
  <img
      src="https://cdn.snipform.io/pdphilip/elasticlens/lens-migrate.gif"
      alt="ElasticLens Migrate"
    />
</div>
See changelog for other minor updates:
**Full Changelog**: https://github.com/pdphilip/elasticlens/compare/v1.1.0...v1.1.0
## v1.0.0 - 2024-09-02
### ElasticLens v1.0.0
ElasticLens is proud to announce its initial release. This powerful and flexible Laravel package is designed to allow developers to search their Laravel models with the convenience of Eloquent and the power of Elasticsearch.
#### Features
- **Eloquent Integration**: Easily index and search your Laravel models with Elasticsearch.
- **Automatic Indexing**: Models are automatically indexed when created, updated, or deleted.
- **Custom Mappings**: Define custom Elasticsearch mappings for your models.
- **Flexible Searching**: Perform simple searches or complex queries using Elasticsearch's full-text search capabilities.
- **Query Builder**: Intuitive query builder for constructing complex Elasticsearch queries.
- **Aggregations**: Support for Elasticsearch aggregations to perform complex data analysis.
- **Pagination**: Built-in support for paginating search results.
- **Console Commands**: Artisan commands for managing indices and performing bulk operations.
- **Model Observers**: Customizable model observation for index builds.

#### Installation

You can install ElasticLens via Composer:

```bash
composer require pdphilip/elasticlens









```
Then run install:

```bash
php artisan lens:install









```
#### Documentation

For detailed documentation and advanced usage, please refer to the [GitHub repository](https://github.com/pdphilip/elasticlens).

#### Feedback and Contributions

Feedback, bug reports, and contributions are highly appreciated. Users and developers are encouraged to open issues or submit pull requests on the GitHub repository. The ElasticLens community looks forward to collaborating and improving the package together.

### Happy searching with ElasticLens!
