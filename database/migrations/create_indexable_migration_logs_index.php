<?php

use Illuminate\Database\Migrations\Migration;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

return new class extends Migration
{
    public function up()
    {
        $connectionName = config('elasticlens.connection') ?? 'elasticsearch';

        Schema::on($connectionName)->deleteIfExists('indexable_migration_logs');

        return Schema::on($connectionName)->create('indexable_migration_logs', function (IndexBlueprint $index) {
            $index->keyword('index_model');
            $index->keyword('state');
            $index->integer('version_major');
            $index->integer('version_minor');
            $index->mapProperty('map', 'flattened')->index(false);
        });
    }

    public function down()
    {
        $connectionName = config('elasticlens.connection') ?? 'elasticsearch';

        return Schema::on($connectionName)->deleteIfExists('indexable_migration_logs');
    }
};
