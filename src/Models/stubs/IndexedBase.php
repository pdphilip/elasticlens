<?php

namespace PDPhilip\ElasticLens\Models\stubs;

use App\Models\Base;
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Builder\IndexField;
use PDPhilip\ElasticLens\IndexModel;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;

class IndexedBase extends IndexModel
{
    protected $baseModel = Base::class;

    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map(Base::class, function (IndexField $field) {});
    }

    public function migrationMap(): array
    {
        return [
            'version' => 1,
            'blueprint' => function (IndexBlueprint $index) {},
        ];
    }
}
