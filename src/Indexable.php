<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens;

use Exception;
use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\Eloquent\LensBuilder;
use PDPhilip\ElasticLens\Observers\BaseModelObserver;
use PDPhilip\ElasticLens\Observers\ObserverRegistry;

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
        $observer = new BaseModelObserver;
        static::saved(fn ($model) => $observer->saved($model));
        static::deleting(fn ($model) => $observer->deleting($model));
        static::deleted(fn ($model) => $observer->deleted($model));

        if (method_exists(static::class, 'restore')) {
            static::restored(fn ($model) => $observer->restored($model));
        }

        ObserverRegistry::registerEmbedded(static::class);
    }

    public static function search(string $phrase): ?Collection
    {
        return self::viaIndex()->searchPhrasePrefix($phrase)->get();
    }

    public static function viaIndex(): LensBuilder
    {
        return self::indexModel()::query()->returnAsBase();
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
