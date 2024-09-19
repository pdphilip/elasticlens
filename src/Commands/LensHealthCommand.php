<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use Illuminate\Console\Command;
use OmniTerm\OmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\HealthCheck;

use function OmniTerm\render;

class LensHealthCommand extends Command
{
    use OmniTerm;

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
            $this->omni->status($loadError['status'], $loadError['name'], $loadError['title'], $loadError['help']);
            $this->newLine();

            return self::FAILURE;
        }
        $health = HealthCheck::check($model);
        render((string) view('elasticlens::cli.components.title', ['title' => $health['title'], 'color' => 'emerald']));
        $this->omni->status($health['indexStatus']['status'], $health['indexStatus']['title'], $health['indexStatus']['name'], $health['indexStatus']['help'] ?? []);
        $this->newLine();
        $this->omni->header('Index Model', 'Value');
        foreach ($health['indexData'] as $detail => $value) {
            $this->omni->row($detail, $value);
        }
        $this->newLine();
        $this->omni->header('Base Model', 'Value');
        foreach ($health['modelData'] as $detail => $value) {
            $this->omni->row($detail, $value);
        }
        $this->newLine();
        $this->omni->header('Build Data', 'Value');
        foreach ($health['buildData'] as $detail => $value) {
            $this->omni->row($detail, $value);
        }
        $this->omni->status($health['configStatus']['status'], $health['configStatus']['name'], $health['configStatus']['title'], $health['configStatus']['help'] ?? []);
        $this->newLine();
        $this->omni->header('Config', 'Value');
        foreach ($health['configData'] as $detail => $value) {
            $this->omni->row($detail, $value);
        }
        $this->newLine();
        if (! $health['observers']) {
            $this->omni->warning('No observers found');
        } else {
            $this->omni->header('Observed Model', 'Type');
            foreach ($health['observers'] as $observer) {
                $this->omni->row($observer['key'], $observer['value']);
            }
        }
        if ($health['configStatusHelp']['critical'] || $health['configStatusHelp']['warning']) {
            $this->newLine();
            $this->omni->info('Config Help');
            if ($health['configStatusHelp']['critical']) {
                foreach ($health['configStatusHelp']['critical'] as $critical) {
                    $this->omni->statusError('Config Error', $critical['name'], $critical['help'] ?? []);
                    $this->newLine();
                }
            }
            if ($health['configStatusHelp']['warning']) {
                foreach ($health['configStatusHelp']['warning'] as $warning) {
                    $this->omni->statusWarning('Config Recommendation', $warning['name'], $warning['help'] ?? []);
                    $this->newLine();
                }
            }
        }

        return self::SUCCESS;
    }
}
