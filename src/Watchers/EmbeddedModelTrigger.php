<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Watchers;

use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Jobs\IndexBuildJob;
use PDPhilip\ElasticLens\Lens;

class EmbeddedModelTrigger
{
    public ?array $settings;

    public ?array $modelChain = [];

    public mixed $model;

    public mixed $baseModel;

    public function __construct($model, $baseModel, $settings)
    {
        $this->model = $model;
        $this->baseModel = $baseModel;
        $this->settings = $settings;
    }

    public function handle($event): void
    {
        $this->modelChain[] = '['.class_basename($this->model).'::'.$event.']';
        $this->fetchRecords($this->model, $this->settings);
    }

    public function fetchRecords($modelInstance, $settings): void
    {
        $this->modelChain[] = class_basename($settings['relation']);
        if ($settings['model'] === $this->baseModel) {
            $this->modelChain[] = class_basename($this->baseModel);
            $this->dispatchBuild($modelInstance, $settings);

            return;
        }

        $modelKey = $settings['whereRelatedField'];
        $modelValue = $modelInstance->{$modelKey};
        $parentKey = $settings['equalsModelField'];
        $parentModel = $settings['model'];
        $parentModel::where($parentKey, $modelValue)->chunk(100, function ($records) use ($settings) {
            foreach ($records as $record) {
                if (! empty($settings['upstream'])) {
                    $this->fetchRecords($record, $settings['upstream']);
                } else {
                    $this->fetchRecords($record, $settings);
                }
            }
        });
    }

    private function dispatchBuild($modelInstance, $settings): void
    {
        $modelInstanceKey = $settings['whereRelatedField'];
        $value = $modelInstance->{$modelInstanceKey};
        $baseKey = $settings['equalsModelField'];
        $observers = implode(' -> ', $this->modelChain);
        $indexModel = Lens::fetchIndexModelClass($this->baseModel);
        $config = IndexConfig::for($indexModel);
        (new $this->baseModel)::where($baseKey, $value)->chunk(100, function ($records) use ($config, $observers) {
            foreach ($records as $record) {
                IndexBuildJob::dispatch($config->indexModel, $record->{$config->baseModelPrimaryKey}, $observers);
            }
        });
    }
}
