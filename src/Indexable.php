<?php

namespace PDPhilip\ElasticLens;

use Exception;
use PDPhilip\Elasticsearch\Eloquent\Builder;
use PDPhilip\Elasticsearch\Eloquent\Model;
use PDPhilip\ElasticLens\Observers\ObserverRegistry;

trait Indexable
{
    public static function bootIndexable()
    {
        ObserverRegistry::register(self::class);
    }

    public static function viaIndex(): Builder
    {
        $indexModel = Lens::fetchIndexModel((new static));

        return $indexModel->query();
    }

    public static function indexSearch($callback)
    {
        $results = static::viaIndex()->tap($callback)->search();

        return static::searchResults($results);
    }

    public static function indexGet($callback)
    {
        $results = static::viaIndex()->tap($callback)->get();

        return static::searchResults($results);
    }

    public static function indexFirst($callback)
    {
        $indexModel = Lens::fetchIndexModel((new static));
        $result = $indexModel->query()->tap($callback)->first();
        if ($result) {
            return $result->asModel();
        }

        return null;

    }

    public static function searchResults($results)
    {
        $indexModel = Lens::fetchIndexModel((new static));

        return $indexModel::collectModels($results);
    }

    public static function indexModel(): Model
    {
        return Lens::fetchIndexModel((new static));
    }

    public function returnIndex()
    {
        $modelId = $this->{$this->getKeyName()};
        $indexModel = Lens::fetchIndexModel($this);

        try {
            return $indexModel->where('_id', $modelId)->first();
        } catch (Exception $e) {

        }

        return null;
    }

    public function buildIndex()
    {
        $modelId = $this->{$this->getKeyName()};
        $indexModel = Lens::fetchIndexModel($this);

        return $indexModel::indexBuild($modelId, 'Direct call from '.get_class($this).' trait');

    }
}
