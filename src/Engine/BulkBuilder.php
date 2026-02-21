<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Engine;

use Exception;
use PDPhilip\ElasticLens\Config\IndexConfig;
use PDPhilip\ElasticLens\Jobs\BulkBuildStateUpdateJob;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Models\IndexableMigrationLog;

class BulkBuilder
{
    private IndexConfig $config;

    private string $migrationVersion;

    private array $buildMaps = [];

    private mixed $result = null;

    private float $startTime;

    public int $skipped = 0;

    /**
     * @throws Exception
     */
    public function __construct(string $baseModel)
    {
        $indexModel = Lens::fetchIndexModelClass($baseModel);
        $this->config = IndexConfig::for($indexModel);

        if (! $this->config->baseModel) {
            throw new Exception('BulkIndexing not available for "'.$indexModel.'": BaseModel not set');
        }

        if (! $this->config->baseModelIndexable) {
            throw new Exception('BulkIndexing not available for "'.$indexModel.'": Base model not indexable');
        }

        $this->migrationVersion = IndexableMigrationLog::getLatestVersion($this->config->indexModelName)
            ?: 'v'.$this->config->migrationMajorVersion.'.0';
        $this->startTime = microtime(true);
    }

    public function setRecords($records): static
    {
        $this->buildMaps = [];
        $this->skipped = 0;
        $this->startTime = microtime(true);

        if (! $records) {
            return $this;
        }

        foreach ($records as $record) {
            $id = $record->{$this->config->baseModelPrimaryKey};
            $result = RecordBuilder::prepareMap($this->config->indexModel, $id);
            $result->attachMigrationVersion($this->migrationVersion);

            if (! $result->skipped) {
                $result->successful('Bulk Ok');
            }

            $this->buildMaps[$id] = $result;
        }

        return $this;
    }

    public function build(): static
    {
        $values = [];
        foreach ($this->buildMaps as $build) {
            if ($build->skipped) {
                $this->skipped++;

                continue;
            }
            $values[] = $build->map;
        }

        if ($values) {
            $this->result = ($this->config->indexModel)::bulkInsert($values);
        }

        $this->updateAnyErrors();
        $this->result['skipped'] = $this->skipped;
        BulkBuildStateUpdateJob::dispatch($this->config->indexModel, $this->config->baseModel, $this->buildMaps);

        return $this;
    }

    public function getResult(): array
    {
        $ms = round((microtime(true) - $this->startTime) * 1000, 0);

        return [
            'took' => [
                'ms' => $ms,
                'sec' => round($ms / 1000, 2),
                'min' => round($ms / 60000, 2),
            ],
            'results' => $this->result,
        ];
    }

    private function updateAnyErrors(): void
    {
        if (! $this->result || empty($this->result['error'])) {
            return;
        }

        foreach ($this->result['error'] as $error) {
            $id = $error['payload']['id'] ?? null;
            if ($id && isset($this->buildMaps[$id])) {
                $build = $this->buildMaps[$id];
                $build->setMessage($error['error']['type'], $error['error']['reason']);
                $build->failed();
            }
        }
    }
}
