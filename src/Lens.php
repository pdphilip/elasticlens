<?php

namespace PDPhilip\ElasticLens;

use PDPhilip\Elasticsearch\Eloquent\Model;

class Lens {

    //----------------------------------------------------------------------
    // Indexes
    //----------------------------------------------------------------------
    public static function fetchIndexModelClass($baseModel): string
    {
        return config('elasticlens.namespaces.indexes').'\\Indexed'.class_basename($baseModel);
    }

    public static function fetchIndexModel($baseModel): Model
    {
        $indexModel = self::fetchIndexModelClass($baseModel);

        return new $indexModel;

    }

    public static function getIndexModel($indexModel): Model
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
