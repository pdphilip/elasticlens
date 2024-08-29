<?php

namespace PDPhilip\ElasticLens;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\Traits\IndexBaseModel;
use PDPhilip\ElasticLens\Traits\IndexFieldMap;
use PDPhilip\ElasticLens\Traits\IndexMigrationMap;
use PDPhilip\Elasticsearch\Eloquent\Builder;
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
        Builder::macro('paginateModels', function ($perPage = 15, $pageName = 'page', $page = null, $options = []) {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
            $items = $this->get()->forPage($page, $perPage);
            $items = $items->map(function ($value) {
                return $value->base;
            });

            return new LengthAwarePaginator(
                $items,
                $this->count(),
                $perPage,
                $page,
                $options
            );
        });
        Collection::macro('asModel', function () {
            return $this->map(function ($value) {
                return $value->base;
            });
        });
    }

    public function paginateModels($perPage = 15, $pageName = 'page', $page = null, $options = [])
    {
        $items = $this->get();
        dd($items);
    }

    public function base()
    {
        return $this->belongsTo($this->baseModel, '_id');
    }

    public function asModel()
    {
        return $this->base;

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
