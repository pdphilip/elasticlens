<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Observers;

use Exception;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Watchers\EmbeddedModelTrigger;

class ObserverRegistry
{
    /**
     * @throws Exception
     */
    public static function register($baseModel): void
    {

        $indexModel = Lens::fetchIndexModelClass($baseModel);

        if (! class_exists($indexModel)) {
            return;
        }
        $observers = (new $indexModel)->getObserverSet();

        if (! empty($observers['base'])) {
            $baseModel::observe(new BaseModelObserver);
        }
        if (! empty($observers['embedded'])) {
            foreach ($observers['embedded'] as $settings) {
                if ($settings['observe']) {
                    $embeddedModel = $settings['relation'];
                    if (! Lens::checkIfWatched($embeddedModel, $indexModel)) {
                        self::watchEmbedded($embeddedModel, $settings, $baseModel);
                    }
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public static function registerWatcher($watchedModel, $indexModel): void
    {
        $indexModelInstance = new $indexModel;
        $observers = $indexModelInstance->getObserverSet();
        $baseModel = $indexModelInstance->getBaseModel();
        if (! empty($observers['embedded'])) {
            foreach ($observers['embedded'] as $settings) {
                if ($watchedModel == $settings['relation'] && $settings['observe']) {
                    self::watchEmbedded($watchedModel, $settings, $baseModel);
                }

            }
        }
    }

    /**
     * @throws Exception
     */
    private static function watchEmbedded($watchedModel, $settings, $baseModel): void
    {
        $watchedModel::saved(function ($model) use ($settings, $baseModel) {
            $watcher = new EmbeddedModelTrigger($model, $baseModel, $settings);
            $watcher->handle('saved');
        });
        $watchedModel::deleted(function ($model) use ($settings, $baseModel) {
            $watcher = new EmbeddedModelTrigger($model, $baseModel, $settings);
            $watcher->handle('deleted');
        });
    }
}
