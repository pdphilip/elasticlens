<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use PDPhilip\ElasticLens\Commands\Scripts\HealthCheck;

use function Termwind\render;

class LensHealthCommand extends Command
{
    public $signature = 'lens:health {model : Base Model Name, example: User}';

    public $description = 'Full health check of the model & model index';

    /**
     * @throws Exception
     */
    public function handle(): int
    {

        $model = $this->argument('model');

        $loadError = HealthCheck::loadErrorCheck($model);
        $this->newLine();
        if ($loadError) {
            render((string) view('elasticlens::cli.components.status', [
                'name' => $loadError['name'],
                'status' => $loadError['status'],
                'title' => $loadError['title'],
                'help' => $loadError['help'],
            ]));
            $this->newLine();

            return self::FAILURE;
        }
        render((string) view('elasticlens::cli.health', [
            'health' => HealthCheck::check($model),
        ]));

        return self::SUCCESS;
    }
}
