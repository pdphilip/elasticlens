<?php

namespace PDPhilip\ElasticLens\Commands;

use Illuminate\Console\Command;
use PDPhilip\ElasticLens\Commands\Scripts\HealthCheck;

use function Termwind\render;

class LensHealthCommand extends Command
{
    public $signature = 'lens:health {model : Base Model Name, example: User}';

    public $description = 'Full health check of the model & model index';

    public function handle(): int
    {

        $model = $this->argument('model');

        $loadError = HealthCheck::loadErrorCheck($model);
        if ($loadError) {
            $this->newLine();
            render(view('elasticlens::cli.components.status', [
                'name' => $loadError['name'],
                'status' => $loadError['status'],
                'title' => $loadError['title'],
                'help' => $loadError['help'],
            ]));
            $this->newLine();

            return self::FAILURE;
        }
        $this->newLine();
        render(view('elasticlens::cli.health', [
            'health' => HealthCheck::check($model),
        ]));

        return self::SUCCESS;
    }
}
