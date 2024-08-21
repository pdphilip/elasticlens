<?php

use Illuminate\Database\Migrations\Migration;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

return new class extends Migration
{
    public function up()
    {
        $connectionName = config('elasticlens.connection') ?? 'elasticsearch';

        Schema::on($connectionName)->deleteIfExists('indexable_build_states');

        return Schema::on($connectionName)->create('indexable_build_states', function (IndexBlueprint $index) {
            $index->keyword('model');
            $index->keyword('model_id');
            $index->keyword('index_model');
            $index->keyword('state');
            $index->keyword('last_source');
            $index->text('last_source');

            $index->mapProperty('state_data', 'flattened')->index(false);
            $index->mapProperty('logs', 'flattened')->index(false);
        });
    }

    public function down()
    {
        $connectionName = config('elasticlens.connection') ?? 'elasticsearch';

        return Schema::on($connectionName)->deleteIfExists('indexable_build_states');
    }
};
