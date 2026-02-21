<?php

use Illuminate\Database\Migrations\Migration;
use PDPhilip\Elasticsearch\Schema\Blueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

return new class extends Migration
{
    public function up()
    {
        $connectionName = config('elasticlens.connection') ?? 'elasticsearch';

        Schema::on($connectionName)->dropIfExists('indexable_migration_logs');

        Schema::on($connectionName)->create('indexable_migration_logs', function (Blueprint $index) {
            $index->keyword('index_model');
            $index->keyword('state');
            $index->integer('version_major');
            $index->integer('version_minor');
            $index->flattened('map')->indexField(false);
        });
    }

    public function down()
    {
        $connectionName = config('elasticlens.connection') ?? 'elasticsearch';

        Schema::on($connectionName)->dropIfExists('indexable_migration_logs');
    }
};
