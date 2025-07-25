<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use OmniTerm\OmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\QualifyModel;
use PDPhilip\ElasticLens\Index\BulkIndexer;
use PDPhilip\ElasticLens\Index\LensState;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Traits\Timer;

use function OmniTerm\asyncFunction;
use function OmniTerm\render;

class LensBuildCommand extends Command
{
    use LensCommands, OmniTerm, Timer;

    public $signature = 'lens:build {model}';

    public $description = 'Rebuilds all index records for the specified model';

    protected mixed $indexModel = null;

    protected mixed $model = null;

    protected mixed $baseModel = null;

    protected mixed $migrate = null;

    protected bool $migrationPassed = false;

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
        $this->initOmni();
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
        render((string) view('elasticlens::cli.components.title', ['title' => 'Rebuild '.$name, 'color' => 'cyan']));
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

        $async = asyncFunction(function () {});
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
        $bulk = new BulkIndexer($this->baseModel);
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

    public function setChunkRate(LensState $health): void
    {
        $config = config('elasticlens.chunk_rates');
        $modelName = get_class($this->baseModel);

        if (isset($config['models'][$modelName]['build'])) {
            $this->chunkRate = $config['models'][$modelName]['build'];
            return;
        }

        $chunk = $config['default'] ?? $this->chunkRate;

        if ($config['relationship_scaling']['enabled'] ?? true) {
            $relationships = count($health->indexModelInstance->getRelationships());
            $thresholds = $config['relationship_scaling']['thresholds'] ?? [
                3 => 750,
                6 => 500,
                9 => 250,
            ];

            krsort($thresholds);
            foreach ($thresholds as $relation_count => $chunkSize) {
                if ($relationships > $relation_count) {
                    $chunk = $chunkSize;
                    break;
                }
            }
        }

        $this->chunkRate = $chunk;
    }
}
