<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PDPhilip\ElasticLens\Models\IndexableBuild;

class BulkBuildStateUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private $indexModel, private $baseModel, private $buildStates) {}

    public function handle(): void
    {
        if (! empty($this->buildStates)) {
            foreach ($this->buildStates as $modelId => $buildState) {
                IndexableBuild::writeState(class_basename($this->baseModel), $modelId, class_basename($this->indexModel), $buildState, 'Bulk Index');
            }
        }
    }
}
