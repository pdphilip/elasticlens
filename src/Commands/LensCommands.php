<?php

namespace PDPhilip\ElasticLens\Commands;

use Exception;
use OmniTerm\OmniTerm;
use PDPhilip\ElasticLens\Commands\Scripts\HealthCheck;
use PDPhilip\ElasticLens\Index\LensMigration;

trait LensCommands
{
    use OmniTerm;

    protected mixed $migrate = null;

    protected bool $migrationPassed = false;

    public function checkModel($model): bool
    {
        $loadError = HealthCheck::loadErrorCheck($model);
        if ($loadError) {
            $this->newLine();
            $this->omni->status($loadError['status'], $loadError['name'], $loadError['title'], $loadError['help']);

            $this->newLine();

            return false;
        }
        try {
            $check = HealthCheck::check($model);
            if (! empty($check['configStatusHelp']['critical'])) {
                foreach ($check['configStatusHelp']['critical'] as $critical) {
                    $this->newLine();
                    $this->omni->statusError('ERROR', $critical['name'], $critical['help']);
                }
                $this->newLine();

                return false;
            }
        } catch (Exception $e) {
            $this->newLine();
            $this->omni->statusError('ERROR', $e->getMessage());
            $this->newLine();

            return false;
        }

        return true;
    }

    //----------------------------------------------------------------------
    // Migrations
    //----------------------------------------------------------------------

    public function migrationStep(): void
    {
        while (! in_array($this->migrate, ['yes', 'no', 'y', 'n'])) {
            $this->migrate = $this->omni->ask('Migrate index?', ['yes', 'no']);
        }
        if (in_array($this->migrate, ['yes', 'y'])) {
            $this->migrationPassed = $this->processMigration($this->indexModel);
        } else {
            $this->migrationPassed = true;
            $this->newLine();
            $this->omni->info('Index migration skipped');
        }
    }

    private function processMigration($indexModel): bool
    {
        $this->omni->newLoader();
        $result = $this->omni->runTask('Migrating Index', function () use ($indexModel) {
            try {
                $migration = new LensMigration($indexModel);
                $migration->runMigration();

                return [
                    'state' => 'success',
                    'message' => 'Migration Successful',
                    'details' => '',
                ];
            } catch (Exception $e) {
                return [
                    'state' => 'error',
                    'message' => 'Migration Failed',
                    'details' => $e->getMessage(),
                ];
            }
        });
        $this->newLine();

        return $result['state'] === 'success';

    }
}
