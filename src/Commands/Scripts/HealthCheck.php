<?php

namespace PDPhilip\ElasticLens\Commands\Scripts;

use Exception;
use PDPhilip\ElasticLens\Index\LensState;
use PDPhilip\ElasticLens\Lens;

final class HealthCheck
{
    public static function loadErrorCheck($model): array|bool
    {

        $modelNameSpace = config('elasticlens.namespaces.models');
        $className = $modelNameSpace.'\\'.$model;
        if (! class_exists($className)) {
            return [
                'name' => 'ERROR',
                'status' => 'error',
                'title' => 'Base Model not found',
                'help' => [
                    $className.' does not exist',
                ],
            ];
        }
        $indexModel = Lens::fetchIndexModelClass($model);
        if (! class_exists($indexModel)) {
            return [
                'name' => 'ERROR',
                'status' => 'error',
                'title' => 'Index Model not found',
                'help' => [
                    $indexModel.' could not be found for base model: '.$model,
                ],
            ];
        }
        try {
            $index = new LensState($indexModel);
            $index->healthCheck();
        } catch (Exception $e) {
            return [
                'name' => 'ERROR',
                'status' => 'error',
                'title' => 'Load Error',
                'help' => [
                    $e->getMessage(),
                ],
            ];
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public static function check($model): array
    {
        $indexModel = Lens::fetchIndexModelClass($model);
        $index = new LensState($indexModel);
        $index = $index->healthCheck();

        $health['title'] = $model.' → '.$index['indexModel'];
        $health['name'] = $index['name'];
        $health['indexStatus'] = $index['state']['status'];
        $health['indexStatus']['title'] = 'Index Status';
        $health['indexData'] = $index['state']['index'];
        $health['modelData'] = $index['state']['model'];
        $health['buildData'] = $index['state']['builds'];
        $health['configStatus'] = [
            'name' => $index['config']['status']['name'],
            'status' => $index['config']['status']['status'],
            'title' => 'Config Status',
        ];
        self::setConfig($health, $index);
        self::setObservers($health, $index);
        $health['configStatusHelp'] = [
            'critical' => $index['config']['status']['critical'],
            'warning' => $index['config']['status']['warning'],
        ];

        return $health;
    }

    private static function setObservers(&$health, $index): void
    {
        $base = $index['config']['observers']['base'];
        $embedded = $index['config']['observers']['embedded'];
        $health['observers'] = [];
        if ($base) {
            $health['observers'][] = [
                'key' => class_basename($base),
                'value' => '(Base)',
            ];
        }
        if (! empty($embedded)) {
            foreach ($embedded as $embed) {
                if ($embed['observe']) {
                    $chain = [];
                    self::buildTriggerChain($embed, $chain);

                    $chain = implode(' → ', $chain);

                    $health['observers'][] = [
                        'key' => class_basename($embed['relation']),
                        'value' => 'Triggers → '.$chain,
                    ];
                }
            }
        }
    }

    public static function buildTriggerChain($embed, &$chain) : void
    {
        $chain[] = class_basename($embed['model']);
        if (! empty($embed['upstream'])) {
            self::buildTriggerChain($embed['upstream'], $chain);
        }
    }

    private static function setConfig(&$health, $index): void
    {

        $health['configData'] = [
            'baseModelIndexable' => $index['config']['base_model_indexable'],
            'baseModelSet' => $index['config']['base_model'],
            'fieldMapSet' => $index['config']['field_map'],
            'migrationMapSet' => $index['config']['migration']['has'],
        ];
        if ($index['config']['migration']['version']) {
            $health['configData']['migrationVersion'] = $index['config']['migration']['version'];
        }
    }
}
