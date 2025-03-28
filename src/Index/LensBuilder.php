<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Index;

use Carbon\Carbon;
use Exception;
use PDPhilip\ElasticLens\Jobs\IndexBuildJob;
use PDPhilip\ElasticLens\Jobs\IndexDeletedJob;
use PDPhilip\ElasticLens\Models\IndexableBuild;
use PDPhilip\ElasticLens\Traits\Timer;

class LensBuilder extends LensIndex
{
    use Timer;

    private BuildResult $buildResult;

    public function customBuild($id, $source, $callback): BuildResult
    {
        $this->_buildInit($id);
        $passed = true;
        try {
            $callback();
        } catch (Exception $e) {
            $this->buildResult->setMessage('Custom Build Failed', 'Exception: '.$e->getMessage());
            $passed = false;
        }
        if ($passed) {
            $this->buildResult->successful('Custom Build Passed');
        }
        IndexableBuild::writeState($this->baseModelName, $id, $this->indexModelName, $this->buildResult, $source);

        return $this->buildResult;
    }

    public function prepareMap($id)
    {
        $this->_buildInit($id);
        $this->_buildMap();

        return $this->buildResult;
    }

    public function buildIndex($id, $source, $migrationVersion = null): BuildResult
    {
        if (! $migrationVersion) {
            $migrationVersion = $this->fetchCurrentMigrationVersion();
        }
        $this->buildProcess($id);
        $this->buildResult->attachMigrationVersion($migrationVersion);
        IndexableBuild::writeState($this->baseModelName, $id, $this->indexModelName, $this->buildResult, $source);

        return $this->buildResult;
    }

    public function dryRun($id): BuildResult
    {
        $this->_buildInit($id);
        $passedSetup = $this->checkSetup();
        if (! $passedSetup) {
            return $this->buildResult->failed();
        }
        $passedMapping = $this->_buildMap();
        if (! $passedMapping) {
            return $this->buildResult->failed();
        }

        return $this->buildResult->successful();

    }

    protected function buildProcess($id): BuildResult
    {
        $this->_buildInit($id);
        $passedSetup = $this->checkSetup();
        if (! $passedSetup) {
            return $this->buildResult->failed();
        }
        $passedMapping = $this->_buildMap();
        if (! $passedMapping) {
            return $this->buildResult->failed();
        }

        $createdIndex = $this->_createIndex();

        if (! $createdIndex) {
            return $this->buildResult->failed();
        }

        return $this->buildResult->successful('Indexed successfully');
    }

    // ----------------------------------------------------------------------
    // Init
    // ----------------------------------------------------------------------

    private function _buildInit($id): void
    {
        $this->buildResult = new BuildResult($id, $this->baseModel, 0);
    }

    // ----------------------------------------------------------------------
    // Setup Check
    // ----------------------------------------------------------------------

    public function checkSetup(): bool
    {

        if (! $this->baseModel) {
            $this->buildResult->setMessage('BaseModel not set', 'Set property: `protected $baseModel = User::class;`');

            return false;
        }

        if (! $this->baseModelIndexable) {
            $this->buildResult->setMessage('Base model not indexable', 'Add trait to base model: `use Indexable`');

            return false;
        }

        return true;
    }

    // ----------------------------------------------------------------------
    // Mapping Process
    // ----------------------------------------------------------------------

    private function _buildMap(): bool
    {
        $id = $this->buildResult->id;
        $fieldMap = $this->fieldMap;

        $model = $this->baseModelInstance->find($id);
        if (! $model) {
            $this->buildResult->setMessage('BaseModel not found', 'BaseModel '.$this->baseModel.' did not have a record for id: '.$id);

            return false;
        }

        if ($model->excludeIndex()) {
            $this->buildResult->skipped = true;
            $this->buildResult->setMessage('BaseModel excluded', 'BaseModel '.$this->baseModel.' has excludeIndex() set to true');

            return false;
        }

        $data = $this->mapId($model, $fieldMap);
        try {
            $dataMap = $this->mapRecordsToFields($fieldMap, $model);
        } catch (Exception $e) {
            $this->buildResult->setMessage('Record Mapping Error', 'Exception: '.$e->getMessage());

            return false;
        }
        $data = $data + $dataMap;

        $this->buildResult->setMap($data);

        return true;
    }

    private function mapId($model, $fieldMap): array
    {
        $data = [];
        $data['id'] = $model->{$model->getKeyName()};
        if (isset($fieldMap['id'])) {
            $data['id'] = $this->setType($data['id'], $fieldMap['id']);
            unset($fieldMap['id']);
        }
        if (isset($fieldMap['id'])) {
            $data['id'] = $this->setType($data['id'], $fieldMap['id']);
            unset($fieldMap['id']);
        }

        return $data;
    }

    private function mapRecordsToFields($fields, $modelData): array
    {

        $data = [];
        if ($fields) {
            foreach ($fields as $field => $type) {
                if (is_array($type)) {
                    $embedFields = $type;
                    $data[$field] = $this->buildEmbeddedRelationship($field, $embedFields, $modelData);

                    continue;
                }

                $value = $modelData->{$field} ?? null;
                if ($value) {
                    $value = $this->setType($value, $type);
                }

                $data[$field] = $value;

            }

            return $data;
        }

        // Else, take what you can get.
        // ....I Also Like to Live Dangerously
        $data = $modelData->toArray();

        // If this was the base model, kick the ID, we has it already
        if ($modelData instanceof $this->baseModel) {
            unset($data[$this->baseModelPrimaryKey]);
        }

        return $data;
    }

    private function buildEmbeddedRelationship($field, $embedFields, $parentData): array
    {
        $relationships = $this->relationships;
        $data = [];
        if (! empty($relationships[$field])) {
            $relationship = $relationships[$field];
            $type = $relationship['type'];
            $relation = $relationship['relation'];
            $whereRelatedField = $relationship['whereRelatedField'];
            $equalsModelField = $relationship['equalsModelField'];
            $modelFieldValue = $parentData->{$equalsModelField};
            $query = $relationship['query'];

            $records = $relation::where($whereRelatedField, $modelFieldValue);
            if ($query) {
                $records = $records->tap($query);
            }
            if ($type == 'hasMany') {
                $records = $records->get();
                if ($records->isNotEmpty()) {
                    foreach ($records as $record) {
                        $data[] = $this->mapRecordsToFields($embedFields, $record);
                    }
                }

                return $data;
            }
            $record = $records->first();
            if ($record) {
                $data = $this->mapRecordsToFields($embedFields, $record);
            }

            return $data;

        }

        return $data;
    }

    // ----------------------------------------------------------------------
    // Create Index
    // ----------------------------------------------------------------------

    public function _createIndex(): bool
    {
        try {
            $modelId = $this->buildResult->id;
            $index = $this->indexModelInstance::find($modelId);
            if (! $index) {
                $index = new $this->indexModelInstance;
            }
            $index->id = $modelId;

            foreach ($this->buildResult->map as $field => $value) {
                $index->{$field} = $value;
            }
            $index->withoutRefresh()->save();
        } catch (Exception $e) {
            $this->buildResult->setMessage('Index build Error', $e->getMessage());

            return false;
        }

        return true;
    }

    // ----------------------------------------------------------------------
    // Delete Index (And Build)
    // ----------------------------------------------------------------------

    public function processDelete($id)
    {
        IndexableBuild::deleteState($this->baseModelName, $id, $this->indexModelName);
        $index = $this->indexModelInstance::find($id);
        $index->delete();
    }

    // ----------------------------------------------------------------------
    // Dispatchers
    // ----------------------------------------------------------------------

    public function dispatchBuild($modelId, $observedModel)
    {
        IndexBuildJob::dispatch($this->indexModel, $modelId, $observedModel);
    }

    public function dispatchDeleted($modelId)
    {
        IndexDeletedJob::dispatch($this->indexModel, $modelId);
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    private function setType($value, $type)
    {
        if ($type == Carbon::class) {
            return Carbon::create($value);
        }
        if (enum_exists($type)) {
            $value = $value->value ?? $value;
            $type = 'string';
        }
        settype($value, $type);

        return $value;
    }
}
