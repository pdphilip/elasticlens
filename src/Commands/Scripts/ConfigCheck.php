<?php

namespace PDPhilip\ElasticLens\Commands\Scripts;

use Exception;
use PDPhilip\ElasticLens\Models\IndexableBuildState;
use PDPhilip\Elasticsearch\Schema\Schema;

final class ConfigCheck
{
    public static function check(): array
    {
        $config = config('elasticlens');

        if (empty($config)) {
            return [
                'config_file' => [
                    'label' => 'Config File',
                    'extra' => 'ElasticLens.php',
                    'status' => 'error',
                    'help' => [
                        'Unexpected error: Config (ElasticLens.php) could not be loaded from anywhere',
                    ],
                ],
            ];
        }
        $output = [];
        //----------------------------------------------------------------------
        // Config File
        //----------------------------------------------------------------------
        self::checkConfig($output);
        $connectionOK = self::checkConnection($output);
        self::checkQueue($output);
        if ($connectionOK) {
            self::buildIndexableBuildState($output);
        }

        return $output;
    }

    private static function checkConfig(&$output): void
    {
        $output['config_file'] = [
            'label' => 'Config File',
            'extra' => 'elasticlens.php',
            'status' => 'ok',
        ];
        if (! file_exists(config_path('elasticlens.php'))) {
            $output['config_file']['status'] = 'warning';
            $output['config_file']['help'] = [
                'Config file not published, using package default. Run: `php artisan lens:install` to publish',
            ];
        }
    }

    private static function checkConnection(&$output): bool
    {
        $connection = config('elasticlens.database');
        $output['connection'] = [
            'label' => 'Connection',
            'extra' => $connection,
            'status' => 'ok',
        ];
        if (! $connection) {
            $output['connection']['status'] = 'error';
            $output['connection']['help'] = [
                "Connection empty in elasticlens.php config. Set 'database' => 'elasticsearch' in config file",
            ];

            return false;
        }
        try {
            Schema::on($connection)->getIndices();
        } catch (Exception $e) {
            $output['connection']['status'] = 'error';
            $output['connection']['help'] = [
                'Connection error on ['.$connection.']: '.$e->getMessage(),
            ];

            //If we get here, return output
            return false;

        }

        return true;
    }

    private static function checkQueue(&$output): void
    {
        $output['queue'] = [
            'label' => 'Queue Priority',
            'extra' => config('elasticlens.queue') ?? 'default (not set)',
            'status' => 'ok',
        ];
    }

    private static function buildIndexableBuildState(&$output): void
    {

        $enabled = config('elasticlens.index_build_state.enabled') ?? false;
        $trim = config('elasticlens.index_build_state.log_trim') ?? false;
        $output['indexable_state'] = [
            'label' => 'IndexableBuildState Model',
            'extra' => '',
            'status' => 'ok',
        ];
        if (! $enabled) {
            $output['indexable_state']['status'] = 'disabled';
            $output['indexable_state']['help'] = [
                'Indexable State Tracking is disabled. Build logs will not be tracked in the indexable_states index',
            ];

            return;
        }
        $output['indexable_state_connect'] = [
            'label' => 'IndexableBuildState Connection',
            'extra' => '',
            'status' => 'ok',
        ];
        $hasIndex = IndexableBuildState::checkHasIndex();
        if (! $hasIndex) {
            $output['indexable_state_connect']['status'] = 'error';
            $output['indexable_state_connect']['help'] = [
                'Indexable State Tracking index not found. Run: php artisan lens:install',
            ];
        }
        $output['indexable_state_log_trim'] = [
            'label' => 'Indexable States Log trim',
            'extra' => $trim,
            'status' => 'ok',
        ];
        if (! $trim) {
            $output['indexable_state_log_trim']['status'] = 'disabled';
            $output['indexable_state_log_trim']['help'] = [
                'Logs will not be stored in the indexable_states index',
            ];
        }

    }
}
