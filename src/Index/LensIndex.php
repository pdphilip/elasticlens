<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Index;

use Exception;
use PDPhilip\ElasticLens\IndexModel;

abstract class LensIndex
{
    public string $indexModel;

    public IndexModel $indexModelInstance;

    public string $indexModelName;

    public string $indexModelTable;

    public bool $indexExists = false;

    public mixed $baseModel = null;

    public bool $baseModelDefined = false;

    public mixed $baseModelInstance;

    public string $baseModelName = '';

    public string $baseModelTable = '';

    public string $baseModelPrimaryKey = 'id';

    public bool $baseModelIndexable = false;

    public array $fieldMap = [];

    public array $observers = [];

    public array $relationships = [];

    public mixed $indexMigration;

    public string $indexMigrationVersion = 'v1.0';

    /**
     * @throws Exception
     */
    public function __construct($indexModel)
    {
        if (! class_exists($indexModel)) {
            throw new Exception($indexModel.' does not exist');
        }
        $instance = (new $indexModel);
        if (! $instance instanceof IndexModel) {
            throw new Exception($indexModel.' is not an IndexModel');
        }
        $this->indexModel = $indexModel;
        $this->indexModelInstance = $instance;
        $migrationSettings = $instance->getMigrationSettings();
        $this->indexModelName = class_basename($indexModel);
        $this->indexModelTable = $instance->getTable();
        $this->indexExists = $instance::indexExists();
        $this->fieldMap = $instance->getFieldSet();
        $this->observers = $instance->getObserverSet();
        $this->relationships = $instance->getRelationships();
        $this->indexMigration = $migrationSettings;
        $this->baseModelDefined = $instance->isBaseModelDefined();
        $baseModel = $instance->getBaseModel();
        if ($baseModel) {
            $this->baseModel = $baseModel;
            $this->baseModelInstance = (new $baseModel);
            $this->baseModelName = class_basename($baseModel);
            $this->baseModelTable = $this->baseModelInstance->getTable();
            $this->baseModelPrimaryKey = $this->baseModelInstance->getKeyName();
            try {
                $baseModel::indexModel();
                $this->baseModelIndexable = true;
            } catch (Exception) {
                $this->baseModelIndexable = false;
            }
        }

    }

    public function fetchCurrentMigrationVersion(): string
    {
        $this->indexMigrationVersion = $this->indexModelInstance->getCurrentMigrationVersion();

        return $this->indexMigrationVersion;
    }
}
