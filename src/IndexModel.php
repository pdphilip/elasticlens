<?php

namespace PDPhilip\ElasticLens;

use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\Traits\IndexBaseModel;
use PDPhilip\ElasticLens\Traits\IndexFieldMap;
use PDPhilip\ElasticLens\Traits\IndexMigrationMap;
use PDPhilip\Elasticsearch\Eloquent\Model;
use RuntimeException;

/** @phpstan-consistent-constructor */
abstract class IndexModel extends Model
{
    use IndexBaseModel, IndexFieldMap, IndexMigrationMap;

    public $connection = 'elasticsearch';

    protected $observeBase = true;

    protected $baseModel;

    public function __construct()
    {
        parent::__construct();
        if (! $this->baseModel) {
            $this->baseModel = $this->guessBaseModelName();
            $this->baseModelDefined = false;
        }
        $this->setConnection(config('elasticlens.database') ?? 'elasticsearch');
    }

    public function asModel()
    {
        if (! empty($this->_id)) {
            return $this->baseModel::find($this->_id);
        }

        return null;

    }

    public static function collectModels($results): Model|Collection
    {
        if ($results instanceof IndexModel) {
            return $results->asModel();
        }
        if ($results instanceof Collection) {
            $collection = new Collection;
            if ($results->isNotEmpty()) {
                foreach ($results as $result) {
                    $collection->push($result->asModel());
                }
            }

            return $collection;
        }
        throw new RuntimeException('Invalid results type');
    }
}
