<?php

namespace PDPhilip\ElasticLens\Traits;

trait IndexMigrationMap
{
    public function migrationMap()
    {
        return [
            'version' => false,
            'blueprint' => false,
        ];
    }

    public function hasIndexMigration(): bool
    {
        return ! empty($this->migrationMap()['blueprint']) && ! empty($this->migrationMap()['version']);
    }

    public function getIndexMigrationVersion()
    {
        return $this->migrationMap()['version'] ?? null;
    }

    public function validateIndexMigrationVersion()
    {
        $migration = $this->migrationMap();

        return ! empty($migration['version']);
    }

    public function validateIndexMigrationBlueprint()
    {
        $migration = $this->migrationMap();
        $blueprint = $migration['blueprint'] ?? null;

        return is_callable($blueprint);
    }
}
