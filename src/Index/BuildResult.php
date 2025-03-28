<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Index;

use PDPhilip\ElasticLens\Traits\Timer;

class BuildResult
{
    use Timer;

    public mixed $id;

    public mixed $model;

    public bool $success = false;

    public bool $skipped = false;

    public string $msg = '';

    public string $details = '';

    public array $map = [];

    public mixed $migration_version;

    public array $took = [];

    public function __construct($id, $model, $migrationVersion = 0)
    {
        $this->id = $id;
        $this->model = $model;
        $this->migration_version = $migrationVersion;
        $this->startTimer();
    }

    public function setMessage($msg, $details = ''): void
    {
        $this->msg = $msg;
        $this->details = $details;
    }

    public function setMap($map): void
    {
        $this->map = $map;
    }

    public function failed(): static
    {
        $this->success = false;
        $this->took = $this->getTime();

        return $this;
    }

    public function successful($details = ''): static
    {
        $this->details = $details;
        $this->success = true;
        $this->took = $this->getTime();

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
}
