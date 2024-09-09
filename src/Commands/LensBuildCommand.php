<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PDPhilip\ElasticLens\Commands\Terminal\LensTerm;
use PDPhilip\ElasticLens\Index\BulkIndexer;
use PDPhilip\ElasticLens\Index\LensState;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Traits\Timer;

use function Termwind\render;

class LensBuildCommand extends Command
{
    use LensCommands, Timer;

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

    protected int $modified = 0;

    protected int $failed = 0;

    /**
     * @throws Exception
     */
    public function handle(): int
    {

        $this->model = $this->argument('model');
        $ok = $this->checkModel($this->model);
        if (! $ok) {
            return self::FAILURE;
        }

        $this->indexModel = Lens::fetchIndexModelClass($this->model);
        $this->newLine();
        render((string) view('elasticlens::cli.components.title', ['title' => 'Rebuild '.class_basename($this->indexModel), 'color' => 'cyan']));
        $this->newLine();
        $health = new LensState($this->indexModel);
        $this->baseModel = $health->baseModel;
        if (! $health->indexExists) {
            render((string) view('elasticlens::cli.components.warning', ['message' => $health->indexModelTable.' index not found']));
            $this->migrate = null;
            $this->migrationStep();
        }
        $health = new LensState($this->indexModel);

        if (! $health->indexExists) {
            render((string) view('elasticlens::cli.components.status', [
                'status' => 'error',
                'name' => 'ERROR',
                'title' => 'Index required',
                'help' => [
                    'Migrate to create the "'.$health->indexModelTable.'" index',
                ],
            ]));

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
                'title' => 'No records found for '.$health->baseModel,
            ]));

            return false;
        }
        $this->startTimer();

        $async = LensTerm::asyncFunction(function () {});
        $async->render((string) view('elasticlens::cli.bulk', [
            'screenWidth' => $async->getScreenWidth(),
            'model' => $this->model,
            'i' => $async->getInterval(),
            'created' => $this->created,
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
                    'updated' => $this->modified,
                    'failed' => $this->failed,
                    'completed' => false,
                    'took' => false,
                ]));
            });
            $this->created += $result['created'];
            $this->modified += $result['modified'];
            $this->failed += $result['failed'];
        });

        $async->render((string) view('elasticlens::cli.bulk', [
            'screenWidth' => $async->getScreenWidth(),
            'model' => $model,
            'i' => $async->getInterval(),
            'created' => $this->created,
            'updated' => $this->modified,
            'failed' => $this->failed,
            'completed' => true,
        ]));

        $this->newLine();
        $name = Str::plural($model);
        $total = $this->created + $this->modified;
        $time = $this->getTime();
        if ($total > 0) {
            render((string) view('elasticlens::cli.components.info', ['message' => 'Indexed '.$total.' '.$name.' in '.$time['sec'].' seconds']));
        } else {
            render((string) view('elasticlens::cli.components.error', ['message' => 'All indexes failed to build']));
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
            'modified' => $result['results']['modified'],
            'failed' => $result['results']['failed'],
        ];
    }

    public function setChunkRate(LensState $health): void
    {
        $chunk = $this->chunkRate;
        $relationships = count($health->indexModelInstance->getRelationships());
        if ($relationships > 3) {
            $chunk = 750;
        }
        if ($relationships > 6) {
            $chunk = 500;
        }
        if ($relationships > 9) {
            $chunk = 250;
        }
        $this->chunkRate = $chunk;
    }
}
