<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Tests\Models\Indexes;

use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\IndexModel;
use PDPhilip\ElasticLens\Tests\Models\Profile;
use PDPhilip\ElasticLens\Tests\Models\User;
use PDPhilip\ElasticLens\Tests\Models\UserLog;
use PDPhilip\Elasticsearch\Schema\Blueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

class IndexedUser extends IndexModel
{
    protected $baseModel = User::class;

    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map($this->baseModel, function ($field) {
            $field->text('name');
            $field->text('email');
            $field->text('status');
            $field->integer('age');
            $field->carbon('created_at');

            $field->embedsOne('profile', Profile::class)
                ->embedMap(function ($embed) {
                    $embed->text('bio');
                    $embed->text('website');
                });

            $field->embedsMany('logs', UserLog::class)
                ->embedMap(function ($embed) {
                    $embed->text('action');
                    $embed->text('details');
                });
        });
    }

    public static function executeSchema(): void
    {
        $schema = Schema::connection('elasticsearch');
        $schema->dropIfExists('indexed_users');
        $schema->create('indexed_users', function (Blueprint $table) {
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
