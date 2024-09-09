<?php

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use PDPhilip\ElasticLens\Commands\Scripts\HealthCheck;
use PDPhilip\ElasticLens\Commands\Terminal\LensTerm;
use PDPhilip\ElasticLens\Index\LensMigration;

use function Termwind\ask;
use function Termwind\render;

trait LensCommands
{
    protected mixed $migrate = null;

    protected bool $migrationPassed = false;

    public function checkModel($model): bool
    {
        $loadError = HealthCheck::loadErrorCheck($model);
        if ($loadError) {
            $this->newLine();

            render((string) view('elasticlens::cli.components.status', [
                'name' => $loadError['name'],
                'status' => $loadError['status'],
                'title' => $loadError['title'],
                'help' => $loadError['help'],
            ]));

            $this->newLine();

            return false;
        }
        try {
            $check = HealthCheck::check($model);
            if (! empty($check['configStatusHelp']['critical'])) {
                foreach ($check['configStatusHelp']['critical'] as $critical) {
                    $this->newLine();
                    render((string) view('elasticlens::cli.components.status', [
                        'name' => 'ERROR',
                        'status' => 'error',
                        'title' => $critical['name'],
                        'help' => $critical['help'],
                    ]));
                }
                $this->newLine();

                return false;
            }
        } catch (Exception $e) {
            $this->newLine();
            render((string) view('elasticlens::cli.components.status', [
                'name' => 'ERROR',
                'status' => 'error',
                'title' => $e->getMessage(),
            ]));
            $this->newLine();

            return false;
        }

        return true;
    }

    //----------------------------------------------------------------------
    // Migrations
    //----------------------------------------------------------------------

    public function migrationStep(): void
    {
        while (! in_array($this->migrate, ['yes', 'no', 'y', 'n'])) {
            $this->migrate = ask((string) view('elasticlens::cli.components.question', ['question' => 'Migrate index?', 'options' => ['yes', 'no']]), ['yes', 'no']);
        }
        if (in_array($this->migrate, ['yes', 'y'])) {
            $this->migrationPassed = $this->processMigration($this->indexModel);
        } else {
            $this->migrationPassed = true;
            $this->newLine();
            render((string) view('elasticlens::cli.components.info', ['message' => 'Index migration skipped']));
        }
    }

    private function processMigration($indexModel): bool
    {

        $async = LensTerm::asyncFunction(function () use ($indexModel) {
            try {
                $migration = new LensMigration($indexModel);
                $migration->runMigration();

                return [
                    'state' => 'success',
                    'message' => 'Migration Successful',
                    'details' => '',
                ];
            } catch (Exception $e) {
                return [
                    'state' => 'error',
                    'message' => 'Migration Failed',
                    'details' => $e->getMessage(),
                ];
            }
        });
        $async->withFailOver((string) view('elasticlens::cli.components.loader', [
            'state' => 'failover',
            'message' => 'Migrating Index',
            'i' => 1,
        ]));
        $result = $async->run(function () use ($async) {
            $async->render((string) view('elasticlens::cli.components.loader', [
                'state' => 'running',
                'message' => 'Migrating Index',
                'i' => $async->getInterval(),
            ]));
        });
        $async->render((string) view('elasticlens::cli.components.loader', [
            'state' => $result['state'],
            'message' => $result['message'],
            'details' => $result['details'],
            'i' => 0,
        ]));
        $this->newLine();

        return $result['state'] === 'success';

    }
}
