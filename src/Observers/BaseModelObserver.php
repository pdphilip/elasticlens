<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Observers;

use Exception;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Jobs\IndexBuildJob;
use PDPhilip\ElasticLens\Jobs\IndexDeletedJob;
use PDPhilip\ElasticLens\Lens;

class BaseModelObserver
{
    public function saved($model): void
    {
        $indexModel = Lens::fetchIndexModelClass($model);
        try {
            $config = IndexConfig::for($indexModel);
            IndexBuildJob::dispatch($config->indexModel, $model->{$config->baseModelPrimaryKey}, $config->baseModel.' (saved)');
        } catch (Exception $e) {
            report($e);
        }
    }

    public function deleting($model): void
    {
        $indexModel = Lens::fetchIndexModelClass($model);
        try {
            // Soft delete with index retention — skip deletion, let deleted() handle the rebuild
            if ($this->isSoftDelete($model)) {
                $config = IndexConfig::for($indexModel);
                if ($config->shouldIndexSoftDeletes()) {
                    return;
                }
            }

            $modelId = $model->{$model->getKeyName()};
            IndexDeletedJob::dispatch($indexModel, $modelId);
        } catch (Exception $e) {
            report($e);
        }
    }

    public function deleted($model): void
    {
        // Only handle soft deletes where the index should be kept
        if (! $this->isTrashed($model)) {
            return;
        }

        $indexModel = Lens::fetchIndexModelClass($model);
        try {
            $config = IndexConfig::for($indexModel);
            if (! $config->shouldIndexSoftDeletes()) {
                return;
            }

            IndexBuildJob::dispatch($config->indexModel, $model->{$config->baseModelPrimaryKey}, $config->baseModel.' (soft deleted)');
        } catch (Exception $e) {
            report($e);
        }
    }

    public function restored($model): void
    {
        $indexModel = Lens::fetchIndexModelClass($model);
        try {
            $config = IndexConfig::for($indexModel);
            IndexBuildJob::dispatch($config->indexModel, $model->{$config->baseModelPrimaryKey}, $config->baseModel.' (restored)');
        } catch (Exception $e) {
            report($e);
        }
    }

    private function isSoftDelete($model): bool
    {
        return method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting();
    }

    private function isTrashed($model): bool
    {
        return method_exists($model, 'trashed') && $model->trashed();
    }
}
