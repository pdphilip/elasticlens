<?php

use Illuminate\Database\Migrations\Migration;
use PDPhilip\Elasticsearch\Schema\Blueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

return new class extends Migration
{
    public function up()
    {
        $connectionName = config('elasticlens.connection') ?? 'elasticsearch';

        Schema::on($connectionName)->deleteIfExists('indexable_builds');

        Schema::on($connectionName)->create('indexable_builds', function (Blueprint $index) {
            $index->keyword('model');
            $index->keyword('model_id');
            $index->keyword('index_model');
            $index->keyword('state');
            $index->keyword('last_source');
            $index->text('last_source');

            $index->flattened('state_data')->indexField(false);
            $index->flattened('logs')->indexField(false);
        });
    }

    public function down()
    {
        $connectionName = config('elasticlens.connection') ?? 'elasticsearch';

        Schema::on($connectionName)->deleteIfExists('indexable_builds');
    }
};
