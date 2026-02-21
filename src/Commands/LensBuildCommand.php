<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use OmniTerm\HasOmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\QualifyModel;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Engine\BulkBuilder;
use PDPhilip\ElasticLens\Index\LensState;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Traits\Timer;

class LensBuildCommand extends Command
{
    use HasOmniTerm, LensCommands, Timer;

    public $signature = 'lens:build {model}';

    public $description = 'Rebuilds all index records for the specified model';

    protected mixed $indexModel = null;

    protected mixed $model = null;

    protected mixed $baseModel = null;

    protected mixed $build = null;

    protected int $chunkRate = 1000;

    protected int $total = 0;

    protected int $created = 0;

    protected int $skipped = 0;

    protected int $modified = 0;

    protected int $failed = 0;

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
        $this->omni->render((string) view('elasticlens::cli.components.title', ['title' => 'Rebuild '.$name, 'color' => 'cyan']));
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
        $this->setChunkRate($health);
        $built = $this->processAsyncBuild($health, $this->model);

        return $built ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @throws Exception
     */
    private function processAsyncBuild($health, $model): bool
    {
        try {
            $recordsCount = $health->baseModel::count();
        } catch (Exception $e) {
            $this->omni->statusError('ERROR', 'Base Model not found', [
                $e->getMessage(),
            ]);

            return false;
        }
        if (! $recordsCount) {
            $this->omni->statusWarning('BUILD SKIPPED', 'No records found for '.$health->baseModel);

            return false;
        }
        $this->startTimer();

        $async = $this->omni->async(function () {});
        $async->render((string) view('elasticlens::cli.bulk', [
            'screenWidth' => $async->getScreenWidth(),
            'model' => $this->model,
            'i' => $async->getInterval(),
            'created' => $this->created,
            'skipped' => $this->skipped,
            'updated' => $this->modified,
            'failed' => $this->failed,
            'completed' => false,
            'took' => false,
        ]));
        $this->baseModel::chunk($this->chunkRate, function ($records) use ($async) {
            $result = $async->withTask(function () use ($records) {
                return $this->bulkInsertTask($records);
            })->run(function () use ($async) {
                $async->render((string) view('elasticlens::cli.bulk', [
                    'screenWidth' => $async->getScreenWidth(),
                    'model' => $this->model,
                    'i' => $async->getInterval(),
                    'created' => $this->created,
                    'skipped' => $this->skipped,
                    'updated' => $this->modified,
                    'failed' => $this->failed,
                    'completed' => false,
                    'took' => false,
                ]));
            });
            $this->created += $result['created'];
            $this->skipped += $result['skipped'];
            $this->modified += $result['modified'];
            $this->failed += $result['failed'];
        });

        $async->render((string) view('elasticlens::cli.bulk', [
            'screenWidth' => $async->getScreenWidth(),
            'model' => $model,
            'i' => $async->getInterval(),
            'created' => $this->created,
            'skipped' => $this->skipped,
            'updated' => $this->modified,
            'failed' => $this->failed,
            'completed' => true,
        ]));

        $this->newLine();
        $name = Str::plural($model);
        $total = $this->created + $this->modified;
        $time = $this->getTime();
        if ($total > 0) {
            $total = number_format($total);
            $this->omni->info('Indexed '.$total.' '.$name.' in '.$time['sec'].' seconds');
        } else {
            $this->omni->error('All indexes failed to build');
        }

        $this->newLine();

        return true;
    }

    /**
     * @throws Exception
     */
    public function bulkInsertTask($records): array
    {
        $bulk = new BulkBuilder($this->baseModel);
        $bulk->setRecords($records)->build();
        $result = $bulk->getResult();

        return [
            'total' => $result['results']['total'],
            'created' => $result['results']['created'],
            'skipped' => $result['results']['skipped'],
            'modified' => $result['results']['modified'],
            'failed' => $result['results']['failed'],
        ];
    }

    public function setChunkRate(LensState $health): int
    {
        $config = IndexConfig::for($health->indexModel);
        if ($config->buildChunkRate > 0) {
            return $this->chunkRate = $config->buildChunkRate;
        }
        $relationships = count($config->relationships);
        $chunk = $this->chunkRate;
        if ($relationships > 3) {
            $chunk = 750;
        }
        if ($relationships > 6) {
            $chunk = 500;
        }
        if ($relationships > 9) {
            $chunk = 250;
        }

        return $this->chunkRate = $chunk;
    }
}
