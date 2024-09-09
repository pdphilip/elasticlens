<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use PDPhilip\ElasticLens\Commands\Terminal\LensTerm;
use PDPhilip\ElasticLens\Index\LensBuilder;
use PDPhilip\ElasticLens\Lens;

use function Termwind\ask;
use function Termwind\render;

class LensMigrateCommand extends Command
{
    use LensCommands;

    public $signature = 'lens:migrate {model} {--force}';

    public $description = 'Migrates the index and Builds the records for the specified model';

    protected mixed $indexModel = null;

    protected mixed $model = null;

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

    /**
     * @throws Exception
     */
    public function handle(): int
    {

        $model = $this->argument('model');
        $force = $this->option('force');

        $ok = $this->checkModel($model);
        if (! $ok) {
            return self::FAILURE;
        }

        $this->model = $model;
        $this->indexModel = Lens::fetchIndexModelClass($model);

        $this->newLine();
        render((string) view('elasticlens::cli.components.title', ['title' => 'Migrate and Build '.class_basename($this->indexModel), 'color' => 'sky']));
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
    // Builds
    //----------------------------------------------------------------------

    public function buildStep(): void
    {
        $question = 'Rebuild Indexes?';
        if (! $this->migrationPassed) {
            $question = 'Migration Failed. Build Anyway?';
        }
        while (! in_array($this->build, ['yes', 'no', 'y', 'n'])) {
            $this->build = ask((string) view('elasticlens::cli.components.question', ['question' => $question, 'options' => ['yes', 'no']]), ['yes', 'no']);
        }
        if (in_array($this->build, ['yes', 'y'])) {
            $this->buildPassed = $this->processBuild($this->indexModel);
        } else {
            render((string) view('elasticlens::cli.components.info', ['message' => 'Build Cancelled']));

            $this->buildPassed = false;
        }
    }

    private function processBuild($indexModel): bool
    {
        try {
            $builder = new LensBuilder($indexModel);
            $recordsCount = $builder->baseModel::count();
        } catch (Exception $e) {
            render((string) view('elasticlens::cli.components.status', [
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
            render((string) view('elasticlens::cli.components.status', [
                'status' => 'warning',
                'name' => 'BUILD SKIPPED',
                'title' => 'No records found for '.$builder->baseModel,
            ]));

            return false;
        }
        $this->buildData['didRun'] = true;
        $this->buildData['total'] = $recordsCount;
        $live = LensTerm::liveRender();
        $live->reRender((string) view('elasticlens::cli.components.progress', [
            'screenWidth' => $live->getScreenWidth(),
            'current' => 0,
            'max' => $this->buildData['total'],
        ]));
        $migrationVersion = $builder->fetchCurrentMigrationVersion();
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

                $live->reRender((string) view('elasticlens::cli.components.progress', [
                    'screenWidth' => $live->getScreenWidth(),
                    'current' => $this->buildData['processed'],
                    'max' => $this->buildData['total'],
                ]));
            }
        });
        $live->reRender((string) view('elasticlens::cli.components.progress', [
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
            render((string) view('elasticlens::cli.components.header-row', ['name' => 'Build Data', 'extra' => null, 'value' => 'Value']));
            render((string) view('elasticlens::cli.components.data-row-value', ['key' => 'Success', 'value' => $this->buildData['success']]));
            render((string) view('elasticlens::cli.components.data-row-value', ['key' => 'Failed', 'value' => $this->buildData['failed']]));
            render((string) view('elasticlens::cli.components.data-row-value', ['key' => 'Total', 'value' => $this->buildData['total']]));
            $this->newLine();
            render((string) view('elasticlens::cli.components.status', [
                'status' => $this->buildData['state'],
                'title' => 'Build Status',
                'name' => $this->buildData['message'],
            ]));
            $this->newLine();
        }
    }
}
