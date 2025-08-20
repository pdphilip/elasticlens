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

    protected mixed $migrateAnyway = null;

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

    // ----------------------------------------------------------------------
    // Migrations
    // ----------------------------------------------------------------------

    public function migrationStep(): void
    {
        while (! in_array($this->migrate, ['yes', 'no', 'y', 'n'])) {
            $this->migrate = $this->omni->ask('Migrate index?', ['yes', 'no']);
        }
        if (in_array($this->migrate, ['yes', 'y'])) {
            $this->migrationValidationStep($this->indexModel);
            while (! in_array($this->migrateAnyway, ['yes', 'no', 'y', 'n', 'cancel'])) {
                $this->migrateAnyway = $this->omni->ask('Migrate anyway?', ['yes', 'no']);
            }
            if ($this->migrateAnyway === 'cancel') {
                exit();
            }
            if (in_array($this->migrateAnyway, ['yes', 'y'])) {
                $this->migrationPassed = $this->processMigration($this->indexModel);

                return;
            }

            $this->migrationPassed = true;
            $this->newLine();
            $this->omni->info('Index migration skipped');

        }
    }

    public function migrationValidationStep($indexModel): void
    {

        $this->omni->newLoader();
        $validated = $this->omni->runTask('Validating Index Migration', function () use ($indexModel) {

            try {
                $migration = new LensMigration($indexModel);
                $validation = $migration->validateMigration();

                if (empty($validation['blueprint'])) {
                    return [
                        'state' => 'warning',
                        'message' => 'No Blueprint Found',
                        'details' => '',
                    ];
                }

                $version = $validation['version'] ?? 'v1';
                if ($validation['validated']) {
                    return [
                        'state' => 'success',
                        'message' => 'Valid Migration Blueprint '.$version,
                        'details' => '',
                    ];
                } else {
                    return [
                        'state' => 'error',
                        'message' => $validation['state'] ?? '',
                        'details' => $validation['message'] ?? '',
                    ];
                }

            } catch (Exception $e) {
                return [
                    'state' => 'error',
                    'message' => 'Migration Validation Error',
                    'details' => $e->getMessage(),
                ];
            }
        });
        if (! empty($validated['state'])) {
            if ($validated['state'] === 'success') {
                $this->migrateAnyway = 'y';

                return;
            }
            if ($validated['state'] === 'warning') {
                return;
            }
        }

        $this->migrateAnyway = 'cancel';
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

        return ! empty($result['state']) && $result['state'] === 'success';

    }
}
