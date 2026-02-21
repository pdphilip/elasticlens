<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PDPhilip\ElasticLens\Engine\RecordBuilder;

class IndexDeletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private $indexModel, private $modelId)
    {
        if (config('elasticlens.queue')) {
            $this->onQueue(config('elasticlens.queue'));
        }
    }

    public function handle(): void
    {
        RecordBuilder::delete($this->indexModel, $this->modelId);
    }
}
