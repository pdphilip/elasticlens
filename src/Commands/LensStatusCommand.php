<?php

namespace PDPhilip\ElasticLens\Commands;

use Illuminate\Console\Command;
use PDPhilip\ElasticLens\Commands\Scripts\ConfigCheck;
use PDPhilip\ElasticLens\Commands\Scripts\IndexCheck;

use function Termwind\render;

class LensStatusCommand extends Command
{
    public $signature = 'lens:status';

    public $description = 'ElasticLens status check';

    public function handle(): int
    {
        $this->newLine();
        render(view('elasticlens::cli.status', [
            'checks' => ConfigCheck::check(),
            'indexes' => IndexCheck::get(),
        ]));
        $this->newLine();

        return self::SUCCESS;
    }
}
