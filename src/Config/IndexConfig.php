<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Config;

use Exception;
use PDPhilip\ElasticLens\IndexModel;

final class IndexConfig
{
    private static array $cache = [];

    public readonly string $indexModel;

    public readonly string $indexModelName;

    public readonly string $indexModelTable;

    public readonly string $baseModel;

    public readonly string $baseModelName;

    public readonly string $baseModelTable;

    public readonly string $baseModelPrimaryKey;

    public readonly bool $baseModelDefined;

    public readonly bool $baseModelIndexable;

    public readonly array $fieldMap;

    public readonly array $relationships;

    public readonly array $observers;

    public readonly int $migrationMajorVersion;

    public readonly mixed $migrationBlueprint;

    public readonly int $buildChunkRate;

    public static function for(string $indexModelClass): self
    {
        return self::$cache[$indexModelClass] ??= new self($indexModelClass);
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    private function __construct(string $indexModelClass)
    {
        if (! class_exists($indexModelClass)) {
            throw new Exception($indexModelClass.' does not exist');
        }

        $instance = new $indexModelClass;
        if (! $instance instanceof IndexModel) {
            throw new Exception($indexModelClass.' is not an IndexModel');
        }

        // Index model
        $this->indexModel = $indexModelClass;
        $this->indexModelName = class_basename($indexModelClass);
        $this->indexModelTable = $instance->getTable();

        // Field map — called once, extract fields + relationships
        $builder = $instance->fieldMap();
        $this->fieldMap = $builder->getFieldMap();
        $this->relationships = $builder->getRelationships();

        // Observer set — uses existing public API
        // (internally calls fieldMap() again, but IndexConfig is cached per-request)
        $this->observers = $instance->getObserverSet();

        // Base model
        $this->baseModelDefined = $instance->isBaseModelDefined();
        $baseModel = $instance->getBaseModel();
        $this->baseModel = $baseModel ?: '';

        if ($this->baseModel && class_exists($this->baseModel)) {
            $baseInstance = new $this->baseModel;
            $this->baseModelName = class_basename($this->baseModel);
            $this->baseModelTable = $baseInstance->getTable();
            $this->baseModelPrimaryKey = $baseInstance->getKeyName();
            $this->baseModelIndexable = method_exists($this->baseModel, 'indexModel');
        } else {
            $this->baseModelName = '';
            $this->baseModelTable = '';
            $this->baseModelPrimaryKey = 'id';
            $this->baseModelIndexable = false;
        }

        // Migration
        $migrationSettings = $instance->getMigrationSettings();
        $this->migrationMajorVersion = $migrationSettings['majorVersion'];
        $this->migrationBlueprint = $migrationSettings['blueprint'];

        $chunkRate = $instance->getBuildChunkRate();
        $this->buildChunkRate = is_int($chunkRate) ? $chunkRate : 0;
    }
}
