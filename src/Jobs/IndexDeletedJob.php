<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PDPhilip\ElasticLens\Index\LensBuilder;

class IndexDeletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private $indexModel, private $modelId)
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
        $builder = new LensBuilder($this->indexModel);
        $builder->processDelete($this->modelId);
    }
}
