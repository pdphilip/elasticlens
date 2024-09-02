# Changelog

All notable changes to `elasticlens` will be documented in this file.

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
