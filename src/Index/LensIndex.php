<?php

namespace PDPhilip\ElasticLens\Index;

use Exception;

abstract class LensIndex
{
    public string $indexModel;

    public mixed $indexModelInstance;

    public string $indexModelName;

    public string $indexModelTable;

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

    public array $indexMigration = [];

    /**
     * @throws Exception
     */
    public function __construct($indexModel)
    {
        if (! class_exists($indexModel)) {
            throw new Exception($indexModel.' does not exist');
        }

        $this->indexModel = $indexModel;
        $this->indexModelInstance = (new $indexModel);
        $this->indexModelName = class_basename($indexModel);
        $this->indexModelTable = $this->indexModelInstance->getTable();
        $this->fieldMap = $this->indexModelInstance->getFieldSet();
        $this->observers = $this->indexModelInstance->getObserverSet();
        $this->relationships = $this->indexModelInstance->getRelationships();
        $this->indexMigration = $this->indexModelInstance->migrationMap();
        $this->baseModelDefined = $this->indexModelInstance->isBaseModelDefined();
        $baseModel = $this->indexModelInstance->getBaseModel();
        if ($baseModel) {
            $this->baseModel = $baseModel;
            $this->baseModelInstance = (new $baseModel);
            $this->baseModelName = class_basename($baseModel);
            $this->baseModelTable = $this->baseModelInstance->getTable();
            $this->baseModelPrimaryKey = $this->baseModelInstance->getKeyName();
            try {
                $baseModel::indexModel();
                $this->baseModelIndexable = true;
            } catch (Exception $e) {

            }
        }

    }
}
