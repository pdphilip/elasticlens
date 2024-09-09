<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens;

use PDPhilip\ElasticLens\Observers\ObserverRegistry;

trait HasWatcher
{
    public static function bootHasWatcher(): void
    {
        $watchers = config('elasticlens.watchers');
        if (isset($watchers[static::class])) {
            foreach ($watchers[static::class] as $index) {
                ObserverRegistry::registerWatcher(self::class, $index);
            }
        }
    }
}
