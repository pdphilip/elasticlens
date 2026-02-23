<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use OmniTerm\HasOmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\QualifyModel;
use PDPhilip\ElasticLens\Index\LensState;
use PDPhilip\ElasticLens\Lens;

class LensBuildCommand extends Command
{
    use BuildsIndex, HasOmniTerm, LensCommands;

    public $signature = 'lens:build {model}';

    public $description = 'Rebuilds all index records for the specified model';

    protected mixed $indexModel = null;

    protected mixed $model = null;

    protected mixed $baseModel = null;

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $model = $this->argument('model');
        $modelCheck = QualifyModel::check($model);
        if (! $modelCheck['qualified']) {
            $this->omni->statusError('ERROR', 'Model not found', ['Model: '.$model]);

            return self::FAILURE;
        }
        $model = $modelCheck['qualified'];
        $this->model = $model;
        $this->indexModel = Lens::fetchIndexModelClass($this->model);
        $this->newLine();
        $name = Str::plural($this->model);
        $this->omni->titleBar('Rebuild '.$name, 'cyan');
        $this->newLine();

        $health = new LensState($this->indexModel);
        $this->baseModel = $health->baseModel;

        if (! $health->indexExists) {
            $this->omni->statusError('ERROR', $health->indexModelTable.' index not found');
            $this->migrate = null;
            $this->migrationStep();
        }

        $health = new LensState($this->indexModel);
        if (! $health->indexExists) {
            $this->omni->statusError('ERROR', 'Index required', [
                'Migrate to create the "'.$health->indexModelTable.'" index',
            ]);

            return self::FAILURE;
        }

        $built = $this->runBulkBuild($this->baseModel, $this->indexModel);

        return $built ? self::SUCCESS : self::FAILURE;
    }
}
