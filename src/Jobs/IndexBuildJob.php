<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexBuildJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private $indexModelClass, private $modelId, private $observedModel)
    {
        if (config('elasticlens.queue')) {
            $this->onQueue(config('elasticlens.queue'));
        }
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->indexModelClass::indexBuild($this->modelId, 'Observed: '.$this->observedModel);
    }
}
