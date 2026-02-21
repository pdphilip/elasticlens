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
        $modelId = $model->{$model->getKeyName()};
        $indexModel = Lens::fetchIndexModelClass($model);
        try {
            IndexDeletedJob::dispatch($indexModel, $modelId);
        } catch (Exception $e) {
            report($e);
        }
    }
}
