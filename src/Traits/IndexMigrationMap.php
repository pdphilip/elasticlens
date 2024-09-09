<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Traits;

use PDPhilip\ElasticLens\Index\MigrationValidator;
use PDPhilip\ElasticLens\Models\IndexableMigrationLog;

trait IndexMigrationMap
{
    protected int $migrationMajorVersion = 1;

    public function migrationMap(): ?callable
    {
        return null;
    }

    public function getMigrationSettings(): array
    {
        return [
            'majorVersion' => $this->migrationMajorVersion,
            'blueprint' => $this->migrationMap(),
        ];
    }

    public function getCurrentMigrationVersion(): string
    {
        $version = IndexableMigrationLog::getLatestVersion(class_basename($this));
        if (! $version) {
            $version = 'v'.$this->migrationMajorVersion.'.0';
        }

        return $version;
    }

    public static function validateIndexMigrationBlueprint(): array
    {
        $indexModel = new static;
        $version = $indexModel->getCurrentMigrationVersion();
        $blueprint = $indexModel->migrationMap();
        $indexModelTable = $indexModel->getTable();
        $validator = new MigrationValidator($version, $blueprint, $indexModelTable);

        return $validator->testMigration();
    }
}
