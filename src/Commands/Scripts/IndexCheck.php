<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands\Scripts;

use Exception;
use PDPhilip\ElasticLens\Index\LensState;
use PDPhilip\ElasticLens\Lens;

final class IndexCheck
{
    public static function get(): array
    {
        $indexModels = Lens::returnAllRegisteredIndexes();
        $indexes = [];
        if (! empty($indexModels)) {
            foreach ($indexModels as $indexModel) {
                try {
                    $index = new LensState($indexModel);
                    $indexes[class_basename($indexModel)] = $index->healthCheck();
                } catch (Exception $e) {
                    // skip, I guess
                }
            }
        }
        $states = [];
        foreach ($indexes as $index) {
            $state['name'] = $index['name'];
            $state['indexStatus'] = $index['state']['status'];
            $state['checks'] = [];
            self::indexState($state['checks'], $index);
            self::modelState($state['checks'], $index);
            self::buildState($state['checks'], $index);
            self::configState($state['checks'], $index);

            $states[] = $state;
        }

        return $states;
    }

    public static function indexState(&$checks, $index): void
    {
        $modelName = $index['state']['index']['modelName'];
        $table = $index['state']['index']['table'];
        $accessible = $index['state']['index']['accessible'];
        $records = $index['state']['index']['records'];
        $checks['indexModel'] = [
            'label' => $modelName,
            'extra' => 'accessible',
            'status' => 'ok',
        ];
        if (! $accessible) {
            $checks['indexModel']['status'] = 'error';
            $checks['indexModel']['help'] = [
                'Index model not accessible',
            ];
        }
        $checks['indexModelRecords'] = [
            'label' => $table.' records',
            'extra' => $records,
            'status' => 'ok',
        ];
        if (! $records) {
            $checks['indexModelRecords']['status'] = 'warning';
        }
    }

    public static function modelState(&$checks, $index): void
    {
        $defined = $index['state']['model']['defined'];
        $modelName = $index['state']['model']['modelName'];
        $table = $index['state']['model']['table'];
        $accessible = $index['state']['model']['accessible'];
        $records = $index['state']['model']['records'];
        if (! $defined) {
            $checks['model'] = [
                'label' => $modelName,
                'extra' => 'defined',
                'status' => 'error',
                'help' => [
                    'Model not defined',
                ],
            ];

            return;
        }

        $checks['model'] = [
            'label' => $modelName,
            'extra' => 'accessible',
            'status' => 'ok',
        ];
        if (! $accessible) {
            $checks['model']['status'] = 'error';
            $checks['model']['help'] = [
                'Index model not accessible',
            ];
        }
        $checks['modelRecords'] = [
            'label' => $table.' records',
            'extra' => $records,
            'status' => 'ok',
        ];
        if (! $records) {
            $checks['modelRecords']['status'] = 'warning';
        }
    }

    public static function buildState(&$checks, $index): void
    {
        $builds = $index['state']['builds'];
        $total = $builds['total'];
        $errors = $builds['errors'];
        $success = $builds['success'];
        if (! $total) {
            $checks['builds'] = [
                'label' => 'Builds (IndexedState)',
                'extra' => 'No builds found',
                'status' => 'warning',
            ];

            return;
        }
        if ($errors) {
            $checks['builds'] = [
                'label' => 'Builds (IndexedState)',
                'extra' => $total.' ('.$errors.')',
                'status' => 'error',
                'help' => [
                    $errors.' index(es) could not be built, check logs',
                ],
            ];

            return;
        }
        $checks['builds'] = [
            'label' => 'Builds (IndexedState)',
            'extra' => $total,
            'status' => 'ok',
        ];

    }

    public static function configState(&$checks, $index): void
    {
        $config = $index['config'];
        $status = $config['status'];
        $checks['config'] = [
            'label' => 'Config State',
            'extra' => $status['name'],
            'status' => $status['status'],
        ];
        if ($status['status'] !== 'ok') {
            $checks['config']['help'] = [
                'For details, run: php artisan lens:health '.$index['state']['model']['modelName'],
            ];
        }
    }
}
