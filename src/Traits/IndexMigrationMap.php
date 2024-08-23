<?php

namespace PDPhilip\ElasticLens\Traits;

use Exception;
use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

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
            'version' => $this->migrationMajorVersion,
            'blueprint' => $this->migrationMap(),
        ];
    }

    public function hasIndexMigration(): bool
    {
        $version = $this->migrationMap()['version'] ?? null;
        $blueprint = $this->migrationMap()['blueprint'] ?? null;
        if ($blueprint) {
            dd($this->validateIndexMigrationBlueprint());
            //test it
            $test = $blueprint((new IndexBlueprint));
            dd($test);
        }

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
        if (is_callable($blueprint)) {

            Schema::deleteIfExists($testName);
            try {
                Schema::create($testName, $blueprint);
                Schema::deleteIfExists($testName);

                return true;
            } catch (Exception $e) {

            }
            Schema::deleteIfExists($testName);

        }

        return false;
    }
}
