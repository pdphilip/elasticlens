<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens;

use PDPhilip\Elasticsearch\Eloquent\Model;

class Lens
{
    //----------------------------------------------------------------------
    // Indexes
    //----------------------------------------------------------------------

    /**
     * Fetch the fully qualified class name of the index model.
     *
     * @return class-string<IndexModel> The fully qualified class name of the index model.
     */
    public static function fetchIndexModelClass($baseModel): string
    {
        return config('elasticlens.namespaces.indexes').'\\Indexed'.class_basename($baseModel);
    }

    public static function fetchIndexModel($baseModel): IndexModel
    {
        $indexModel = self::fetchIndexModelClass($baseModel);

        return new $indexModel;

    }

    public static function getIndexModel($indexModel): IndexModel
    {
        $indexModel = config('elasticlens.namespaces.indexes').'\\'.$indexModel;

        return new $indexModel;
    }

    public static function returnAllRegisteredIndexes()
    {
        $indexes = [];

        foreach (glob(app_path(config('elasticlens.app_paths.indexes').'*.php')) as $file) {
            $indexModel = (config('elasticlens.namespaces.indexes').'\\'.basename($file, '.php'));
            $indexes[] = (new $indexModel)::class;
        }

        return $indexes;
    }

    public static function checkIfWatched($model, $indexModel): bool
    {
        $watchers = config('elasticlens.watchers');
        if (isset($watchers[$model])) {
            return in_array($indexModel, $watchers[$model]);
        }

        return false;
    }
}
