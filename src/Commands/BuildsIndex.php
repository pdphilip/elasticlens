<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Support\Str;
use OmniTerm\Async\Spinner;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Engine\BulkBuilder;
use PDPhilip\ElasticLens\Traits\Timer;

trait BuildsIndex
{
    use Timer;

    protected int $chunkRate = 1000;

    protected int $created = 0;

    protected int $modified = 0;

    protected int $skipped = 0;

    protected int $failed = 0;

    /**
     * @throws Exception
     */
    protected function runBulkBuild(string $baseModel, string $indexModel): bool
    {
        try {
            $recordsCount = $baseModel::count();
        } catch (Exception $e) {
            $this->omni->statusError('ERROR', 'Base Model not found', [$e->getMessage()]);

            return false;
        }

        if (! $recordsCount) {
            $this->omni->statusWarning('BUILD SKIPPED', 'No records found for '.$baseModel);

            return false;
        }

        $this->calculateChunkRate($indexModel);
        $this->resetBuildCounters();
        $this->startTimer();

        $name = Str::title(class_basename($baseModel));
        $task = $this->omni->liveTask('Building '.$name.' ', Spinner::Dots);
        $task->row('Created', 0, 'text-sky-500');
        $task->row('Updated', 0, 'text-emerald-500');
        $task->row('Skipped', 0, 'text-amber-500');
        $task->row('Failed', 0, 'text-rose-500');

        $baseModel::chunk($this->chunkRate, function ($records) use ($task, $baseModel) {
            $result = $task->run(fn () => $this->bulkInsertChunk($records, $baseModel));

            $task->increment('Created', $result['created']);
            $task->increment('Updated', $result['modified']);
            $task->increment('Skipped', $result['skipped']);
            $task->increment('Failed', $result['failed']);

            $this->created += $result['created'];
            $this->modified += $result['modified'];
            $this->skipped += $result['skipped'];
            $this->failed += $result['failed'];
        });

        $task->finish('Build complete');
        $this->showBuildSummary($baseModel);

        return true;
    }

    protected function showBuildSummary(string $baseModel): void
    {
        $this->newLine();
        $name = Str::plural(class_basename($baseModel));
        $total = $this->created + $this->modified;
        $time = $this->getTime();

        if ($total > 0) {
            $this->omni->info('Indexed '.number_format($total).' '.$name.' in '.$time['sec'].' seconds');
        } else {
            $this->omni->error('All indexes failed to build');
        }

        $this->newLine();
    }

    protected function showBuildTable(): void
    {
        $total = $this->created + $this->modified + $this->skipped + $this->failed;
        $this->omni->tableHeader('Build Data', 'Value');
        $this->omni->tableRow('Created', (string) $this->created, null, 'text-sky-500');
        $this->omni->tableRow('Updated', (string) $this->modified, null, 'text-emerald-500');
        $this->omni->tableRow('Skipped', (string) $this->skipped, null, 'text-amber-500');
        $this->omni->tableRow('Failed', (string) $this->failed, null, 'text-rose-500');
        $this->omni->tableRow('Total', (string) $total, null, 'text-emerald-500');
    }

    /**
     * @throws Exception
     */
    private function bulkInsertChunk($records, string $baseModel): array
    {
        $bulk = new BulkBuilder($baseModel);
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

    private function calculateChunkRate(string $indexModel): void
    {
        $config = IndexConfig::for($indexModel);
        if ($config->buildChunkRate > 0) {
            $this->chunkRate = $config->buildChunkRate;

            return;
        }

        $relationships = count($config->relationships);
        if ($relationships > 9) {
            $this->chunkRate = 250;
        } elseif ($relationships > 6) {
            $this->chunkRate = 500;
        } elseif ($relationships > 3) {
            $this->chunkRate = 750;
        }
    }

    private function resetBuildCounters(): void
    {
        $this->created = 0;
        $this->modified = 0;
        $this->skipped = 0;
        $this->failed = 0;
    }
}
