<?php

namespace PDPhilip\ElasticLens\Commands;

use Illuminate\Console\Command;

class LensMakeCommand extends Command
{
    public $signature = 'lens:make {model}';

    public $description = 'Make a new index for the specified model';

    public function handle(): int
    {

        $model = $this->argument('model');

        //TODO
        return self::SUCCESS;
    }
}
