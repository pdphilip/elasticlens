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

    public static function search(string $phrase): ?Collection
    {
        return self::viaIndex()->searchPhrasePrefix($phrase)->getBase();
    }

    public static function viaIndex(): IndexModel|Builder
    {
        return Lens::fetchIndexModelClass((new static))::query();
    }

    /**
     * @return class-string<IndexModel>
     */
    public static function indexModel(): string
    {
        return Lens::fetchIndexModelClass((new static));
    }

    public function returnIndex(): ?IndexModel
    {
        $modelId = $this->{$this->getKeyName()};

        try {
            return self::indexModel()::where('id', $modelId)->first();
        } catch (Exception $e) {
            return null;
        }
    }

    public function buildIndex(): array
    {
        $modelId = $this->{$this->getKeyName()};

        return self::indexModel()::indexBuild($modelId, 'Direct call from '.get_class($this).' trait')->toArray();
    }

    public function excludeIndex(): bool
    {
        return false;
    }

    public function removeIndex(): bool
    {
        $modelId = $this->{$this->getKeyName()};

        try {
            $deleted = self::indexModel()::destroy($modelId);
        } catch (Exception $e) {
            return false;
        }

        return $deleted == 1;
    }
}
