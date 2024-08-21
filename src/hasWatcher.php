<?php

namespace PDPhilip\ElasticLens;

use PDPhilip\ElasticLens\Observers\ObserverRegistry;

trait hasWatcher
{
    public static function bootHasWatcher()
    {
        $watchers = config('elasticlens.watchers');
        if (isset($watchers[static::class])) {
            foreach ($watchers[static::class] as $index) {
                ObserverRegistry::registerWatcher(self::class, $index);
            }
        }
    }
}
