<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Index;

use Exception;
use PDPhilip\ElasticLens\Jobs\BulkBuildStateUpdateJob;
use PDPhilip\ElasticLens\Lens;
use PDPhilip\ElasticLens\Traits\Timer;

class BulkIndexer
{
    use Timer;

    protected mixed $baseModel;

    protected mixed $indexModel;

    protected mixed $builder;

    protected mixed $migrationVersion;

    protected mixed $buildMaps;

    protected mixed $took;

    protected mixed $result;

    public int $skipped = 0;

    /**
     * @throws Exception
     */
    public function __construct($baseModel)
    {
        $this->baseModel = $baseModel;
        $this->indexModel = Lens::fetchIndexModelClass($baseModel);
        $this->builder = new LensBuilder($this->indexModel);
        if (! $this->builder->baseModel) {
            throw new Exception('BulkIndexing not available for "'.$this->indexModel.'": BaseModel not set - Set property: `protected $baseModel = User::class;`');
        }

        if (! $this->builder->baseModelIndexable) {
            throw new Exception('BulkIndexing not available for "'.$this->indexModel.'": Base model not indexable - Add trait to base model: `use Indexable`');
        }

        $this->migrationVersion = $this->builder->fetchCurrentMigrationVersion();
    }

    public function setRecords($records): static
    {
        $this->clearActivity();
        $builds = [];
        if ($records) {
            foreach ($records as $record) {
                $id = $record->{$this->builder->baseModelPrimaryKey};
                $setup = $this->builder->prepareMap($id);
                $setup->attachMigrationVersion($this->migrationVersion);
                // Assume ok
                if (! $setup->skipped) {
                    $setup->successful('Bulk Ok');
                }

                $builds[$id] = $setup;
            }
        }

        $this->buildMaps = $builds;

        return $this;
    }

    public function build(): static
    {
        $values = [];
        if ($this->buildMaps) {
            foreach ($this->buildMaps as $build) {
                if ($build->skipped) {
                    $this->skipped++;

                    continue;
                }
                $values[] = $build->map;
            }
        }
        if ($values) {
            $this->result = $this->indexModel::bulkInsert($values);
        }
        $this->updateAnyErrors();
        $this->result['skipped'] = $this->skipped;
        BulkBuildStateUpdateJob::dispatch($this->indexModel, $this->baseModel, $this->buildMaps);
        $this->took = $this->getTime();

        return $this;
    }

    public function updateAnyErrors(): void
    {
        if ($this->result) {
            if (! empty($this->result['error'])) {
                $builds = $this->buildMaps;
                foreach ($this->result['error'] as $error) {
                    $id = $error['payload']['id'] ?? null;
                    $build = $builds[$id];
                    $build->setMessage($error['error']['type'], $error['error']['reason']);
                    $build->failed();
                    $builds[$id] = $build;
                }
                $this->buildMaps = $builds;
            }
        }
    }

    public function getResult(): array
    {
        return [
            'took' => $this->took,
            'results' => $this->result,
        ];
    }

    public function clearActivity(): void
    {
        $this->buildMaps = [];
        $this->startTimer();

    }
}
