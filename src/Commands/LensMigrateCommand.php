<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use OmniTerm\HasOmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\QualifyModel;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Engine\RecordBuilder;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Models\IndexableMigrationLog;

class LensMigrateCommand extends Command
{
    use HasOmniTerm, LensCommands;

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
        $this->omni->render((string) view('elasticlens::cli.components.title', ['title' => 'Migrate and Build '.class_basename($this->indexModel), 'color' => 'sky']));
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
            $config = IndexConfig::for($indexModel);
            $recordsCount = $config->baseModel::count();
        } catch (Exception $e) {
            $this->omni->statusError('ERROR', 'Base Model not found', [$e->getMessage()]);

            return false;
        }
        if (! $recordsCount) {
            $this->omni->statusWarning('BUILD SKIPPED', 'No records found for '.$config->baseModel);

            return false;
        }
        $this->buildData['didRun'] = true;
        $this->buildData['total'] = $recordsCount;
        $bar = $this->omni->progressBar($this->buildData['total'])->steps();
        $migrationVersion = IndexableMigrationLog::getLatestVersion($config->indexModelName)
            ?: 'v'.$config->migrationMajorVersion.'.0';
        $chunkSize = $this->chunkRate;
        if ($config->buildChunkRate > 0) {
            $chunkSize = $config->buildChunkRate;
        }
        $bar->start();
        $config->baseModel::chunk($chunkSize, function ($records) use ($config, $migrationVersion, $bar) {
            foreach ($records as $record) {
                $id = $record->{$config->baseModelPrimaryKey};
                $build = RecordBuilder::build($config->indexModel, $id, 'Index Rebuild', $migrationVersion);
                $this->buildData['processed']++;
                if (! empty($build->success)) {
                    $this->buildData['success']++;
                } elseif (! empty($build->skipped)) {
                    $this->buildData['skipped']++;
                } else {
                    $this->buildData['failed']++;
                }
                $bar->advance();
            }
        });
        $bar->finish();
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
            $this->omni->tableHeader('Build Data', 'Value');
            $this->omni->tableRow('Success', $this->buildData['success'], null, 'text-emerald-500');
            $this->omni->tableRow('Skipped', $this->buildData['skipped'], null, 'text-amber-500');
            $this->omni->tableRow('Failed', $this->buildData['failed'], null, 'text-rose-500');
            $this->omni->tableRow('Total', $this->buildData['total'], null, 'text-emerald-500');
            $this->newLine();
            $this->omni->status($this->buildData['state'], 'Build Status', $this->buildData['message']);
            $this->newLine();
        }
    }
}
