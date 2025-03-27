<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Index;

use Exception;
use Illuminate\Support\Str;
use PDPhilip\ElasticLens\Models\IndexableBuild;

class LensState extends LensIndex
{
    public function healthCheck()
    {
        $indexTable = $this->indexModelTable;
        $name = Str::title(str_replace('_', ' ', $indexTable));

        return [
            'name' => $name,
            'indexModel' => $this->indexModelName,
            'state' => $this->_indexStatusCheck(),
            'config' => $this->_configCheck(),
        ];

    }

    public function getObservedModelNames()
    {
        return $this->observers;
    }

    private function _indexStatusCheck(): array
    {

        $indexData['modelName'] = $this->indexModelName;
        $indexData['table'] = $this->indexModelTable;
        $indexData['accessible'] = false;
        $indexData['records'] = 0;
        try {

            $indexData['records'] = $this->indexModelInstance::count();
            $indexData['accessible'] = true;
        } catch (Exception $e) {

        }
        $modelData['modelName'] = $this->baseModelName;
        $modelData['defined'] = $this->baseModelDefined;
        $modelData['table'] = $this->baseModelTable;
        $modelData['accessible'] = false;
        $modelData['records'] = 0;
        if ($this->baseModel) {
            try {
                $modelData['records'] = $this->baseModel::count();
                $modelData['accessible'] = true;
            } catch (Exception $e) {

            }
        }
        $builds = [];
        $builds['success'] = 0;
        $builds['errors'] = 0;
        $builds['total'] = IndexableBuild::countModelRecords($this->indexModelName);
        if ($builds['total'] > 0) {
            $builds['errors'] = IndexableBuild::countModelErrors($this->indexModelName);
            $builds['success'] = $builds['total'] - $builds['errors'];
        }
        $status = $this->_buildStatus($indexData, $modelData, $builds);

        return [
            'index' => $indexData,
            'model' => $modelData,
            'builds' => $builds,
            'status' => $status,
        ];
        // Status calc
    }

    private function _configCheck(): array
    {
        $config = [
            'base_model_indexable' => $this->baseModelIndexable,
            'base_model' => $this->baseModelDefined,
            'field_map' => ! empty($this->fieldMap),
            'migration' => [
                'has' => $this->indexMigration['blueprint'] !== null,
                'version' => $this->fetchCurrentMigrationVersion(),
            ],
            'observers' => $this->getObservedModelNames(),
            'status' => [],
        ];
        $config['status'] = $this->_buildConfigStatus($config);

        return $config;
    }

    private function _buildStatus($indexData, $modelData, $builds): array
    {
        if (! $indexData['accessible']) {
            return [
                'status' => 'error',
                'name' => 'Index Not Accessible',
                'help' => ['Check ES connection & index migration for ('.$indexData['table'].')'],
            ];
        }
        if (! $modelData['accessible']) {
            return [
                'status' => 'error',
                'name' => 'Model Not Accessible',
                'help' => ['Base model ('.$modelData['modelName'].') could not be reached. Does it exist?'],
            ];
        }
        if ($builds['errors']) {
            return [
                'status' => 'warning',
                'name' => 'Index Build Errors',
                'help' => ['Some indexed could not be built, check logs. Total: '.$builds['errors']],
            ];
        }

        if ($modelData['records'] !== $indexData['records']) {
            return [
                'status' => 'warning',
                'name' => 'Indexes out of sync',
                'help' => ['Index count ('.$indexData['records'].') does not match model count ('.$modelData['records'].')'],
            ];
        }
        if (! $builds['total']) {
            return [
                'status' => 'warning',
                'name' => 'No indexes',
                'help' => ['No indexes found for ('.$indexData['modelName'].')'],
            ];
        }

        return [
            'status' => 'ok',
            'name' => 'Index Synced',
        ];
    }

    private function _buildConfigStatus($config): array
    {
        $critical = [];
        $warning = [];
        if (! $config['base_model_indexable']) {
            $critical[] = [
                'status' => 'error',
                'name' => 'Base Model Not Indexable',
                'help' => ['Add trait to base model: `use Indexable`'],
            ];
        }
        if (! $config['base_model']) {

            $baseModel = $this->baseModel;
            if (! $baseModel) {
                $baseModel = 'MyModel::class';
            } else {
                $baseModel = class_basename($this->baseModel).'::class';
            }

            $warning[] = [
                'status' => 'warning',
                'name' => 'Base Model Not Set',
                'help' => [
                    'Base model will be guessed',
                    'Set property: `protected $baseModel = '.$baseModel.';`',
                ],
            ];
        }
        if (! $config['field_map']) {
            $warning[] = [
                'status' => 'warning',
                'name' => 'Field Map Recommended',
                'help' => [
                    'Fields will taken as is from the base model when it is indexed',
                    'You can define your own in the Index Model with: `public function fieldMap(): IndexBuilder`',
                ],
            ];
        }
        if (! $config['observers']['base'] && ! $config['observers']['embedded']) {
            $warning[] = [
                'status' => 'warning',
                'name' => 'No Models Observed',
                'help' => ['There are no events that will trigger indexing'],
            ];
        }
        if (! $config['migration']['has']) {
            $warning[] = [
                'status' => 'warning',
                'name' => 'Migration Recommended',
                'help' => [
                    'The index will automatically infer the field types as data is indexed',
                    'You can define your own in the Index Model with: `public function migrationMap(): MigrationBuilder`',
                ],
            ];
        }
        $status = [
            'status' => 'ok',
            'name' => 'OK',
            'critical' => $critical,
            'warning' => $warning,
        ];
        if ($warning) {
            $warnings = count($warning);
            $status['status'] = 'warning';
            $status['name'] = '1 Config Warning';
            if ($warnings > 1) {
                $status['name'] = $warnings.' Config Warnings';
            }

        }
        if ($critical) {
            $status['status'] = 'error';
            $status['name'] = 'Critical Config Error';

        }

        return $status;

    }
}
