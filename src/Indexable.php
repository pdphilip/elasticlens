<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens;

use Exception;
use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\Observers\ObserverRegistry;
use PDPhilip\Elasticsearch\Eloquent\Builder;

/**
 * Trait Indexable
 */
trait Indexable
{
    /**
     * @throws Exception
     */
    public static function bootIndexable(): void
    {
        ObserverRegistry::register(self::class);
    }

    public static function search($phrase): ?Collection
    {
        $query = self::viaIndex();

        return $query->phrase($phrase)->search()->asBase();
    }

    public static function viaIndex(): IndexModel|Builder
    {
        $indexModel = Lens::fetchIndexModelClass((new static));

        return $indexModel::query();

    }

    /**
     * Fetch the fully qualified class name of the index model.
     *
     * @return class-string<IndexModel> The fully qualified class name of the index model.
     */
    public static function indexModel(): string
    {
        return Lens::fetchIndexModelClass((new static));
    }

    public function returnIndex(): ?IndexModel
    {
        $modelId = $this->{$this->getKeyName()};
        $indexModel = Lens::fetchIndexModelClass($this);

        try {
            return $indexModel::where('id', $modelId)->first();
        } catch (Exception $e) {

        }

        return null;
    }

    public function buildIndex(): array
    {
        $modelId = $this->{$this->getKeyName()};
        $indexModel = Lens::fetchIndexModelClass($this);

        $build = $indexModel::indexBuild($modelId, 'Direct call from '.get_class($this).' trait');

        return $build->toArray();

    }
}
