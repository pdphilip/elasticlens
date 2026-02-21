<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Engine;

use Exception;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Models\IndexableBuild;
use PDPhilip\ElasticLens\Models\IndexableMigrationLog;

class RecordBuilder
{
    public static function build(string $indexModelClass, mixed $id, string $source, ?string $migrationVersion = null): BuildResult
    {
        $config = IndexConfig::for($indexModelClass);
        $result = new BuildResult($id, $config->baseModel, 0);

        if (! self::validateSetup($config, $result)) {
            return $result->failed();
        }

        if (! $migrationVersion) {
            $migrationVersion = IndexableMigrationLog::getLatestVersion($config->indexModelName)
                ?: 'v'.$config->migrationMajorVersion.'.0';
        }
        $result->attachMigrationVersion($migrationVersion);

        if (! self::mapRecord($config, $id, $result)) {
            return $result->failed();
        }

        if (! self::saveToIndex($config, $result)) {
            return $result->failed();
        }

        $result->successful('Indexed successfully');
        self::writeState($config, $id, $result, $source);

        return $result;
    }

    public static function dryRun(string $indexModelClass, mixed $id): BuildResult
    {
        $config = IndexConfig::for($indexModelClass);
        $result = new BuildResult($id, $config->baseModel, 0);

        if (! self::validateSetup($config, $result)) {
            return $result->failed();
        }

        if (! self::mapRecord($config, $id, $result)) {
            return $result->failed();
        }

        return $result->successful();
    }

    public static function prepareMap(string $indexModelClass, mixed $id): BuildResult
    {
        $config = IndexConfig::for($indexModelClass);
        $result = new BuildResult($id, $config->baseModel, 0);
        self::mapRecord($config, $id, $result);

        return $result;
    }

    public static function customBuild(string $indexModelClass, mixed $id, string $source, callable $callback): BuildResult
    {
        $config = IndexConfig::for($indexModelClass);
        $result = new BuildResult($id, $config->baseModel, 0);

        try {
            $callback();
            $result->successful('Custom Build Passed');
        } catch (Exception $e) {
            $result->setMessage('Custom Build Failed', 'Exception: '.$e->getMessage());
            $result->failed();
        }

        self::writeState($config, $id, $result, $source);

        return $result;
    }

    public static function delete(string $indexModelClass, mixed $id): void
    {
        $config = IndexConfig::for($indexModelClass);
        IndexableBuild::deleteState($config->baseModelName, $id, $config->indexModelName);
        $config->indexModel::destroy($id);
    }

    // ----------------------------------------------------------------------
    // Pipeline steps
    // ----------------------------------------------------------------------

    private static function validateSetup(IndexConfig $config, BuildResult $result): bool
    {
        if (! $config->baseModel) {
            $result->setMessage('BaseModel not set', 'Set property: `protected $baseModel = User::class;`');

            return false;
        }

        if (! $config->baseModelIndexable) {
            $result->setMessage('Base model not indexable', 'Add trait to base model: `use Indexable`');

            return false;
        }

        return true;
    }

    private static function mapRecord(IndexConfig $config, mixed $id, BuildResult $result): bool
    {
        $query = $config->baseModel::query();
        if (method_exists($config->baseModel, 'withTrashed')) {
            $query = $query->withTrashed();
        }
        $model = $query->find($id);
        if (! $model) {
            $result->setMessage('BaseModel not found', 'BaseModel '.$config->baseModel.' did not have a record for id: '.$id);

            return false;
        }

        try {
            $data = RecordMapper::map($model, $config);
        } catch (Exception $e) {
            $result->setMessage('Record Mapping Error', 'Exception: '.$e->getMessage());

            return false;
        }

        if ($data === null) {
            $result->skipped = true;
            $result->setMessage('BaseModel excluded', 'BaseModel '.$config->baseModel.' has excludeIndex() set to true');

            return false;
        }

        $result->setMap($data);

        return true;
    }

    private static function saveToIndex(IndexConfig $config, BuildResult $result): bool
    {
        try {
            $index = $config->indexModel::find($result->id);
            if (! $index) {
                $index = new ($config->indexModel);
            }
            $index->id = $result->id;

            foreach ($result->map as $field => $value) {
                $index->{$field} = $value;
            }
            $index->withoutRefresh()->save();
        } catch (Exception $e) {
            $result->setMessage('Index build Error', $e->getMessage());

            return false;
        }

        return true;
    }

    private static function writeState(IndexConfig $config, mixed $id, BuildResult $result, string $source): void
    {
        if (! IndexableBuild::isEnabled()) {
            return;
        }
        IndexableBuild::writeState($config->baseModelName, $id, $config->indexModelName, $result, $source);
    }
}
