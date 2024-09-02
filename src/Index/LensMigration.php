<?php

namespace PDPhilip\ElasticLens\Index;

use Exception;
use PDPhilip\ElasticLens\Enums\IndexableMigrationLogState;
use PDPhilip\ElasticLens\Models\IndexableBuild;
use PDPhilip\ElasticLens\Models\IndexableMigrationLog;
use PDPhilip\ElasticLens\Traits\Timer;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

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

            $this->_state['state']['has_blueprint'] = $this->indexModelInstance->migrationMap() !== null;
            $this->_state['state']['migration_version'] = $this->fetchCurrentMigrationVersion();

        } catch (Exception $e) {
            $this->_state['error'] = true;
            $this->_state['message'] = $e->getMessage();
        }

        return $this->_state;
    }

    public function runMigration(): bool
    {
        $validBlueprint = false;
        $tableName = $this->indexModelTable;
        $blueprint = $this->indexMigration['blueprint'] ?? null;
        $version = $this->indexMigration['majorVersion'] ?? null;
        if ($blueprint) {
            $validBlueprint = $this->validateMigration()['validated'];
        }
        try {
            IndexableBuild::deleteStateModel($this->indexModelName);

            Schema::deleteIfExists($tableName);
            if (! $validBlueprint) {
                $map = Schema::create($tableName, function (IndexBlueprint $index) {
                    $index->date('created_at');
                });
                IndexableMigrationLog::saveMigrationLog($this->indexModelName, $version, IndexableMigrationLogState::UNDEFINED, $map);
            } else {
                $map = Schema::create($tableName, $blueprint);
                IndexableMigrationLog::saveMigrationLog($this->indexModelName, $version, IndexableMigrationLogState::SUCCESS, $map);
            }

            return true;
        } catch (Exception $e) {
            $map = ['error' => $e->getMessage()];
            IndexableMigrationLog::saveMigrationLog($this->indexModelName, $version, IndexableMigrationLogState::FAILED, $map);
        }

        return false;
    }

    public function validateMigration(): array
    {

        $test = new MigrationValidator(
            $this->fetchCurrentMigrationVersion(),
            $this->indexModelInstance->migrationMap(),
            $this->indexModelTable
        );

        return $test->testMigration();
    }
}
