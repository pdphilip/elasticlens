<?php

namespace PDPhilip\ElasticLens\Index;

use Exception;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use PDPhilip\ElasticLens\Models\IndexableBuildState;
use PDPhilip\ElasticLens\Traits\Timer;

class LensMigration extends LensIndex
{
    use Timer;

    private array $_state = [
        'error' => false,
        'message' => '',
        'state' => [
            'index_model' => '',
            'index_count' => 0,
            'base_model' => false,
            'base_count' => 0,
            'has_blueprint' => false,
            'migration_version' => false,
        ],
    ];

    public function migrationState(): array
    {
        $this->_state['state']['index_model'] = $this->indexModelName;
        try {

            $indexCount = 0;
            try {
                $indexCount = $this->indexModelInstance::count();
            } catch (Exception $e) {

            }
            $this->_state['state']['index_count'] = $indexCount;

            if ($this->baseModel) {
                $this->_state['state']['base_model'] = $this->baseModelName;
                $this->_state['state']['base_count'] = $this->baseModelInstance::count();
            }

            $this->_state['state']['has_blueprint'] = $this->indexModelInstance->validateIndexMigrationBlueprint();
            $this->_state['state']['migration_version'] = $this->indexModelInstance->getIndexMigrationVersion();

        } catch (Exception $e) {
            $this->_state['error'] = true;
            $this->_state['message'] = $e->getMessage();
        }

        return $this->_state;
    }

    public function runMigration(): bool
    {
        try {
            IndexableBuildState::deleteStateModel($this->indexModelName);
            $tableName = $this->indexModelTable;
            $blueprint = $this->indexMigration['blueprint'] ?? null;
            Schema::deleteIfExists($tableName);
            if (! $blueprint) {
                Schema::create($tableName, function (IndexBlueprint $index) {
                    $index->date('created_at');
                });
            } else {
                Schema::create($tableName, $blueprint);
            }

            return true;
        } catch (Exception $e) {

        }
        return false;
    }
}
