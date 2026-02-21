<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Observers;

use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Watchers\EmbeddedModelTrigger;

class ObserverRegistry
{
    public static function register($baseModel): void
    {
        $indexModel = Lens::fetchIndexModelClass($baseModel);
        if (! class_exists($indexModel)) {
            return;
        }

        $config = IndexConfig::for($indexModel);

        if (! empty($config->observers['base'])) {
            $baseModel::observe(new BaseModelObserver);
        }

        if (! empty($config->observers['embedded'])) {
            foreach ($config->observers['embedded'] as $settings) {
                if ($settings['observe']) {
                    $embeddedModel = $settings['relation'];
                    if (! Lens::checkIfWatched($embeddedModel, $indexModel)) {
                        self::watchEmbedded($embeddedModel, $settings, $config->baseModel);
                    }
                }
            }
        }
    }

    public static function registerWatcher($watchedModel, $indexModel): void
    {
        $config = IndexConfig::for($indexModel);

        if (! empty($config->observers['embedded'])) {
            foreach ($config->observers['embedded'] as $settings) {
                if ($watchedModel == $settings['relation'] && $settings['observe']) {
                    self::watchEmbedded($watchedModel, $settings, $config->baseModel);
                }
            }
        }
    }

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
