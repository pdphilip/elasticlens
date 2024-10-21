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
     */
    public static function fetchIndexModelClass($baseModel): string
    {
        $baseModel = is_object($baseModel) ? get_class($baseModel) : $baseModel;
        $parts = explode('\\', $baseModel);
        $baseModel = array_pop($parts);
        $baseModelNamespace = implode('\\', $parts);
        $namespace = config('elasticlens.namespaces');
        if (empty($namespace[$baseModelNamespace])) {
            abort(500, 'Namespace not configured for '.$baseModelNamespace);
        }
        $indexNamespace = $namespace[$baseModelNamespace];

        return $indexNamespace.'\\Indexed'.$baseModel;
    }

    public static function returnAllRegisteredIndexes()
    {
        $indexes = [];
        $paths = config('elasticlens.index_paths');
        foreach ($paths as $path => $namespace) {
            foreach (glob(base_path($path.'*.php')) as $file) {
                $indexModel = $namespace.'\\'.basename($file, '.php');
                $indexes[] = (new $indexModel)::class;
            }
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
