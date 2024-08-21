<?php

namespace PDPhilip\ElasticLens\Watchers;

use Exception;
use PDPhilip\ElasticLens\Index\LensBuilder;
use PDPhilip\ElasticLens\Lens;

class EmbeddedModelTrigger
{
    public ?array $settings;

    public ?array $modelChain;

    public mixed $model;

    public mixed $baseModel;

    public function __construct($model, $baseModel, $settings)
    {
        $this->model = $model;
        $this->baseModel = $baseModel;
        $this->settings = $settings;
    }

    /**
     * @throws Exception
     */
    public function handle($event): void
    {
        $this->modelChain[] = '['.class_basename($this->model).'::'.$event.']';
        $this->fetchRecords($this->model, $this->settings);
    }

    /**
     * @throws Exception
     */
    public function fetchRecords($modelInstance, $settings): void
    {
        $this->modelChain[] = class_basename($settings['relation']);
        if ($settings['model'] === $this->baseModel) {
            $this->modelChain[] = class_basename($this->baseModel);
            $this->_dispatchBuild($modelInstance, $settings);
        } else {
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

    }

    /**
     * @throws Exception
     */
    public function _dispatchBuild($modelInstance, $settings): void
    {
        $modelInstanceKey = $settings['whereRelatedField'];
        $value = $modelInstance->{$modelInstanceKey};
        $baseKey = $settings['equalsModelField'];
        $observers = implode(' -> ', $this->modelChain);
        $indexModel = Lens::fetchIndexModelClass($this->baseModel);
        $builder = new LensBuilder($indexModel);
        (new $this->baseModel)::where($baseKey, $value)->chunk(100, function ($records) use ($builder, $observers) {
            foreach ($records as $record) {
                $builder->dispatchBuild($record->{$builder->baseModelPrimaryKey}, $observers);
            }
        });
    }
}
