<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Illuminate\Console\Command;
use OmniTerm\OmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\ConfigCheck;
use PDPhilip\ElasticLens\Commands\Scripts\IndexCheck;

use function OmniTerm\render;

class LensStatusCommand extends Command
{
    use OmniTerm;

    public $signature = 'lens:status';

    public $description = 'ElasticLens status check';

    public function handle(): int
    {
        $this->initOmni();

        $this->newLine();
        render((string) view('elasticlens::cli.components.title', ['title' => 'ElasticLens Status', 'color' => 'teal']));
        $this->newLine();
        $checks = ConfigCheck::check();
        $indexes = IndexCheck::get();

        $this->omni->header('Config', 'Status', 'Value');
        foreach ($checks as $check) {
            $this->omni->rowAsStatus($check['label'], $check['status'], $check['extra'] ?? null, $check['help'] ?? []);
        }
        $this->newLine(2);
        if (! empty($indexes)) {
            foreach ($indexes as $index) {
                $this->omni->status($index['indexStatus']['status'], $index['name'], $index['indexStatus']['name'], $index['indexStatus']['help'] ?? []);
                foreach ($index['checks'] as $check) {
                    $this->omni->rowAsStatus($check['label'], $check['status'], $check['extra'] ?? null, $check['help'] ?? []);
                }
                $this->newLine(2);
            }
        } else {
            $this->omni->statusError('ERROR', 'No indexes found');
            $this->newLine(2);
        }

        return self::SUCCESS;
    }
}
