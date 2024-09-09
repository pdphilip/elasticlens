<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens;

use Exception;
use PDPhilip\ElasticLens\Observers\ObserverRegistry;
use PDPhilip\Elasticsearch\Eloquent\Builder;
use PDPhilip\Elasticsearch\Eloquent\Model;

trait Indexable
{
    public static function bootIndexable(): void
    {
        ObserverRegistry::register(self::class);
    }

    public static function search($phrase)
    {
        $indexModel = Lens::fetchIndexModel((new static));

        return $indexModel->phrase($phrase)->search()->asModel();
    }

    public static function viaIndex(): Builder
    {
        $indexModel = Lens::fetchIndexModel((new static));

        return $indexModel->query();
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

        $build = $indexModel::indexBuild($modelId, 'Direct call from '.get_class($this).' trait');

        return $build->toArray();

    }
}
