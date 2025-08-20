<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use OmniTerm\OmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\QualifyModel;
use PDPhilip\ElasticLens\Index\LensBuilder;
use PDPhilip\ElasticLens\Lens;

use function OmniTerm\render;

class LensMigrateCommand extends Command
{
    use LensCommands, OmniTerm;

    public $signature = 'lens:migrate {model} {--force}';

    public $description = 'Migrates the index and Builds the records for the specified model';

    protected mixed $indexModel = null;

    protected mixed $model = null;

    protected mixed $build = null;

    protected int $chunkRate = 1000;

    protected array $buildData = [
        'didRun' => false,
        'processed' => 0,
        'success' => 0,
        'skipped' => 0,
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
        $this->initOmni();
        $model = $this->argument('model');
        $force = $this->option('force');
        $modelCheck = QualifyModel::check($model);
        if (! $modelCheck['qualified']) {
            $this->omni->statusError('ERROR', 'Model not found', ['Model: '.$model]);

            return self::FAILURE;
        }
        $model = $modelCheck['qualified'];
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

    // ----------------------------------------------------------------------
    // Builds
    // ----------------------------------------------------------------------

    public function buildStep(): void
    {
        $question = 'Rebuild Indexes?';
        if (! $this->migrationPassed) {
            $question = 'Migration Failed. Build Anyway?';
        }
        while (! in_array($this->build, ['yes', 'no', 'y', 'n'])) {
            $this->build = $this->omni->ask($question, ['yes', 'no']);
        }
        if (in_array($this->build, ['yes', 'y'])) {
            $this->buildPassed = $this->processBuild($this->indexModel);
        } else {
            $this->omni->info('Build Cancelled');
            $this->buildPassed = false;
        }
    }

    private function processBuild($indexModel): bool
    {
        try {
            $builder = new LensBuilder($indexModel);
            $recordsCount = $builder->baseModel::count();
        } catch (Exception $e) {
            $this->omni->statusError('ERROR', 'Base Model not found', [$e->getMessage()]);

            return false;
        }
        if (! $recordsCount) {
            $this->omni->statusWarning('BUILD SKIPPED', 'No records found for '.$builder->baseModel);

            return false;
        }
        $this->buildData['didRun'] = true;
        $this->buildData['total'] = $recordsCount;
        $this->omni->createSimpleProgressBar($this->buildData['total']);
        $migrationVersion = $builder->fetchCurrentMigrationVersion();
        $chunkSize = $this->chunkRate;
        if ($modelBuildChunkRate = $builder->indexModelInstance->getBuildChunkRate()) {
            $chunkSize = $modelBuildChunkRate;
        }
        $builder->baseModel::chunk($chunkSize, function ($records) use ($builder, $migrationVersion) {
            foreach ($records as $record) {
                $id = $record->{$builder->baseModelPrimaryKey};
                $build = $builder->buildIndex($id, 'Index Rebuild', $migrationVersion);
                $this->buildData['processed']++;
                if (! empty($build->success)) {
                    $this->buildData['success']++;
                } elseif (! empty($build->skipped)) {
                    $this->buildData['skipped']++;
                } else {
                    $this->buildData['failed']++;
                }
                $this->omni->progressAdvance();
            }
        });
        $this->omni->progressFinish();
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
            $this->omni->header('Build Data', 'Value');
            $this->omni->row('Success', $this->buildData['success'], null, 'text-emerald-500');
            $this->omni->row('Skipped', $this->buildData['skipped'], null, 'text-amber-500');
            $this->omni->row('Failed', $this->buildData['failed'], null, 'text-rose-500');
            $this->omni->row('Total', $this->buildData['total'], null, 'text-emerald-500');
            $this->newLine();
            $this->omni->status($this->buildData['state'], 'Build Status', $this->buildData['message']);
            $this->newLine();
        }
    }
}
