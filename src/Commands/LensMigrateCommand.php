<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use OmniTerm\HasOmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\QualifyModel;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Lens;

class LensMigrateCommand extends Command
{
    use BuildsIndex, HasOmniTerm, LensCommands;

    public $signature = 'lens:migrate {model} {--force}';

    public $description = 'Migrates the index and Builds the records for the specified model';

    protected mixed $indexModel = null;

    protected mixed $model = null;

    protected mixed $build = null;

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
        $this->omni->titleBar('Migrate and Build '.class_basename($this->indexModel), 'sky');
        $this->newLine();

        $this->migrate = $force ? 'yes' : null;
        $this->build = $force ? 'yes' : null;
        $this->migrationStep();
        $this->newLine();
        $this->buildStep();
        $this->newLine();

        if ($this->buildPassed) {
            $this->showBuildTable();
            $state = $this->buildState();
            $this->omni->status($state['status'], 'Build Status', $state['message']);
            $this->newLine();
        }

        return $this->buildPassed ? self::SUCCESS : self::FAILURE;
    }

    // ======================================================================
    // Build Step
    // ======================================================================

    private function buildStep(): void
    {
        $question = 'Rebuild Indexes?';
        if (! $this->migrationPassed) {
            $question = 'Migration Failed. Build Anyway?';
        }

        while (! in_array($this->build, ['yes', 'no', 'y', 'n'])) {
            $this->build = $this->omni->ask($question, ['yes', 'no']);
        }

        if (in_array($this->build, ['no', 'n'])) {
            $this->omni->info('Build Cancelled');

            return;
        }

        $config = IndexConfig::for($this->indexModel);
        $this->buildPassed = $this->runBulkBuild($config->baseModel, $this->indexModel);
    }

    private function buildState(): array
    {
        if (! $this->failed) {
            return ['status' => 'success', 'message' => 'Indexes Synced'];
        }

        $total = $this->created + $this->modified + $this->skipped + $this->failed;
        if ($this->failed === $total) {
            return ['status' => 'error', 'message' => 'All Builds Failed'];
        }

        return ['status' => 'warning', 'message' => 'Some Build Errors'];
    }
}
