<?php

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use PDPhilip\ElasticLens\Commands\Scripts\HealthCheck;
use PDPhilip\ElasticLens\Commands\Terminal\LensTerm;
use PDPhilip\ElasticLens\Index\LensBuilder;
use PDPhilip\ElasticLens\Index\LensMigration;
use PDPhilip\ElasticLens\Lens;

use function Termwind\ask;
use function Termwind\render;

class LensBuildCommand extends Command
{
    public $signature = 'lens:build {model} {--force}';

    public $description = 'Builds the index for the specified model';

    protected mixed $indexModel = null;

    protected mixed $model = null;

    protected mixed $migrate = null;

    protected bool $migrationPassed = false;

    protected mixed $build = null;

    protected array $buildData = [
        'didRun' => false,
        'processed' => 0,
        'success' => 0,
        'failed' => 0,
        'total' => 0,
        'state' => 'error',
        'message' => '',
    ];

    protected bool $buildPassed = false;

    public function handle(): int
    {

        $model = $this->argument('model');
        $force = $this->option('force');

        $loadError = HealthCheck::loadErrorCheck($model);
        if ($loadError) {
            $this->newLine();

            render(view('elasticlens::cli.partials.status', [
                'name' => $loadError['name'],
                'status' => $loadError['status'],
                'title' => $loadError['title'],
                'help' => $loadError['help'],
            ]));

            $this->newLine();

            return self::FAILURE;
        }
        $this->model = $model;
        $this->indexModel = Lens::fetchIndexModelClass($model);
        $this->newLine();
        render(view('elasticlens::cli.partials.title', ['title' => 'Rebuild '.class_basename($this->indexModel), 'color' => 'sky']));
        $this->newLine();
        $this->migrate = $force ? 'yes' : null;
        $this->build = $force ? 'yes' : null;
        $this->migrationStep();
        $this->newLine();
        $this->buildStep();
        $this->newLine();
        $this->showStatus();
        $this->newLine();

        return $this->buildPassed ? self::SUCCESS : self::FAILURE;
    }

    //----------------------------------------------------------------------
    // Migrations
    //----------------------------------------------------------------------

    public function migrationStep(): void
    {
        while (! in_array($this->migrate, ['yes', 'no', 'y', 'n'])) {
            $this->migrate = ask(view('elasticlens::cli.partials.question', ['question' => 'Migrate index?', 'options' => ['yes', 'no']]), ['yes', 'no']);
        }
        if (in_array($this->migrate, ['yes', 'y'])) {
            $this->migrationPassed = $this->processMigration($this->indexModel);
        } else {
            $this->migrationPassed = true;

            render(view('elasticlens::cli.partials.info', ['message' => 'Index migration skipped']));
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
        $async->withFailOver(view('elasticlens::cli.partials.loader', [
            'state' => 'failover',
            'message' => 'Migrating Index',
            'i' => 1,
        ]));
        $result = $async->run(function () use ($async) {
            $async->render(view('elasticlens::cli.partials.loader', [
                'state' => 'running',
                'message' => 'Migrating Index',
                'i' => $async->getInterval(),
            ]));
        });
        $async->render(view('elasticlens::cli.partials.loader', [
            'state' => $result['state'],
            'message' => $result['message'],
            'details' => $result['details'],
            'i' => 0,
        ]));
        $this->newLine();

        return $result['state'] === 'success';

    }

    //----------------------------------------------------------------------
    // Builds
    //----------------------------------------------------------------------

    public function buildStep(): void
    {
        $question = 'Rebuild Indexes?';
        if (! $this->migrationPassed) {
            $question = 'Migration Failed. Build Anyway?';
        }
        while (! in_array($this->build, ['yes', 'no', 'y', 'n'])) {
            $this->build = ask(view('elasticlens::cli.partials.question', ['question' => $question, 'options' => ['yes', 'no']]), ['yes', 'no']);
        }
        if (in_array($this->build, ['yes', 'y'])) {
            $this->buildPassed = $this->processBuild($this->indexModel);
        } else {
            render(view('elasticlens::cli.partials.info', ['message' => 'Build Cancelled']));

            $this->buildPassed = false;
        }
    }

    private function processBuild($indexModel): bool
    {
        try {
            $builder = new LensBuilder($indexModel);
            $recordsCount = $builder->baseModel::count();
        } catch (Exception $e) {
            render(view('elasticlens::cli.partials.status', [
                'status' => 'error',
                'name' => 'ERROR',
                'title' => 'Base Model not found',
                'help' => [
                    $e->getMessage(),
                ],
            ]));

            return false;
        }
        if (! $recordsCount) {
            render(view('elasticlens::cli.partials.status', [
                'status' => 'warning',
                'name' => 'BUILD SKIPPED',
                'title' => 'No records found for '.$builder->baseModel,
            ]));

            return false;
        }
        $this->buildData['didRun'] = true;
        $this->buildData['total'] = $recordsCount;
        $live = LensTerm::liveRender();
        $live->reRender(view('elasticlens::cli.partials.progress', [
            'screenWidth' => $live->getScreenWidth(),
            'current' => 0,
            'max' => $this->buildData['total'],
        ]));
        $migrationVersion = $builder->getCurrentMigrationVersion();
        $builder->baseModel::chunk(100, function ($records) use ($builder, $live, $migrationVersion) {
            foreach ($records as $record) {
                $id = $record->{$builder->baseModelPrimaryKey};
                $build = $builder->buildIndex($id, 'Index Rebuild', $migrationVersion);
                $this->buildData['processed']++;
                if (! empty($build->success)) {
                    $this->buildData['success']++;
                } else {
                    $this->buildData['failed']++;
                }

                $live->reRender(view('elasticlens::cli.partials.progress', [
                    'screenWidth' => $live->getScreenWidth(),
                    'current' => $this->buildData['processed'],
                    'max' => $this->buildData['total'],
                ]));
            }
        });
        $live->reRender(view('elasticlens::cli.partials.progress', [
            'screenWidth' => $live->getScreenWidth(),
            'current' => $this->buildData['total'],
            'max' => $this->buildData['total'],
        ]));
        $this->buildData['state'] = 'success';
        $this->buildData['message'] = 'Indexes Synced';
        if ($this->buildData['failed']) {
            $this->buildData['state'] = 'warning';
            $this->buildData['message'] = 'Some Build Errors';
            if ($this->buildData['failed'] === $this->buildData['processed']) {
                $this->buildData['state'] = 'error';
                $this->buildData['message'] = 'All Builds Failed';
            }
        }

        return true;
    }

    private function showStatus(): void
    {
        if ($this->buildData['didRun']) {
            render(view('elasticlens::cli.partials.header-row', ['name' => 'Build Data', 'extra' => null, 'value' => 'Value']));
            render(view('elasticlens::cli.partials.data-row-value', ['key' => 'Success', 'value' => $this->buildData['success']]));
            render(view('elasticlens::cli.partials.data-row-value', ['key' => 'Failed', 'value' => $this->buildData['failed']]));
            render(view('elasticlens::cli.partials.data-row-value', ['key' => 'Total', 'value' => $this->buildData['total']]));
            $this->newLine();
            render(view('elasticlens::cli.partials.status', [
                'status' => $this->buildData['state'],
                'title' => 'Build Status',
                'name' => $this->buildData['message'],
            ]));
            $this->newLine();
        }
    }
}
