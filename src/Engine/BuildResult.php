<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Engine;

class BuildResult
{
    public mixed $id;

    public mixed $model;

    public bool $success = false;

    public bool $skipped = false;

    public string $msg = '';

    public string $details = '';

    public array $map = [];

    public mixed $migration_version;

    public array $took = [];

    private float $startTime;

    public function __construct($id, $model, $migrationVersion = 0)
    {
        $this->id = $id;
        $this->model = $model;
        $this->migration_version = $migrationVersion;
        $this->startTime = microtime(true);
    }

    public function setMessage(string $msg, string $details = ''): void
    {
        $this->msg = $msg;
        $this->details = $details;
    }

    public function setMap(array $map): void
    {
        $this->map = $map;
    }

    public function failed(): static
    {
        $this->success = false;
        $this->took = $this->elapsed();

        return $this;
    }

    public function successful(string $details = ''): static
    {
        $this->details = $details;
        $this->success = true;
        $this->took = $this->elapsed();

        return $this;
    }

    public function attachMigrationVersion($version): static
    {
        $this->migration_version = $version;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'model' => $this->model,
            'success' => $this->success,
            'msg' => $this->msg,
            'details' => $this->details,
            'map' => $this->map,
            'migration_version' => $this->migration_version,
            'took' => $this->took,
        ];
    }

    private function elapsed(): array
    {
        $ms = round((microtime(true) - $this->startTime) * 1000, 0);

        return [
            'ms' => $ms,
            'sec' => round($ms / 1000, 2),
            'min' => round($ms / 60000, 2),
        ];
    }
}
