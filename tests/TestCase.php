<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Tests;

use OmniTerm\OmniTermServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use PDPhilip\ElasticLens\ElasticLensServiceProvider;
use PDPhilip\Elasticsearch\ElasticServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ElasticServiceProvider::class,
            OmniTermServiceProvider::class,
            ElasticLensServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // SQLite for base models (User, Profile, etc.)
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);

        // Elasticsearch for index models
        $app['config']->set('database.connections.elasticsearch', [
            'driver' => 'elasticsearch',
            'auth_type' => 'http',
            'hosts' => ['http://localhost:9200'],
            'options' => [
                'logging' => true,
            ],
        ]);

        // ElasticLens config
        $app['config']->set('elasticlens.database', 'elasticsearch');
        $app['config']->set('elasticlens.index_soft_deletes', false);
        $app['config']->set('elasticlens.queue', null);
        $app['config']->set('elasticlens.index_build_state.enabled', false);
        $app['config']->set('elasticlens.namespaces', [
            'PDPhilip\\ElasticLens\\Tests\\Models' => 'PDPhilip\\ElasticLens\\Tests\\Models\\Indexes',
        ]);
        $app['config']->set('elasticlens.index_paths', []);
    }
}
