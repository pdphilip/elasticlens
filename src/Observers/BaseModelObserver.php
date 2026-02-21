<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Observers;

use Exception;
use PDPhilip\ElasticLens\Index\LensBuilder;
use PDPhilip\ElasticLens\Lens;

class BaseModelObserver
{
    public function saved($model): void
    {
        $indexModel = Lens::fetchIndexModelClass($model);
        try {
            $builder = new LensBuilder($indexModel);
            $builder->dispatchBuild($model->{$builder->baseModelPrimaryKey}, ($builder->baseModel).' (saved)');
        } catch (Exception $e) {
            report($e);
        }
    }

    public function deleting($model): void
    {
        $modelId = $model->{$model->getKeyName()};
        $indexModel = Lens::fetchIndexModelClass($model);
        try {
            $index = new LensBuilder($indexModel);
            $index->dispatchDeleted($modelId);
        } catch (Exception $e) {
            report($e);
        }
    }
}
