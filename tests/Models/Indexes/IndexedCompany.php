<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Tests\Models\Indexes;

use PDPhilip\ElasticLens\IndexModel;
use PDPhilip\ElasticLens\Tests\Models\Company;
use PDPhilip\Elasticsearch\Schema\Blueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

class IndexedCompany extends IndexModel
{
    protected $baseModel = Company::class;

    // No fieldMap override — uses default (all attributes)

    public static function executeSchema(): void
    {
        $schema = Schema::connection('elasticsearch');
        $schema->dropIfExists('indexed_companies');
        $schema->create('indexed_companies', function (Blueprint $table) {
            $table->date('created_at');
            $table->date('updated_at');
        });
    }
}
