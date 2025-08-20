<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Index;

use Exception;
use PDPhilip\Elasticsearch\Schema\Schema;

final class MigrationValidator
{
    protected mixed $version;

    protected mixed $blueprint;

    protected bool $validated = false;

    protected string $state = 'uninitiated';

    protected ?string $indexModelTable;

    protected string $message = '';

    protected array $indexMap = [];

    public function __construct($version, $blueprint, $indexModelTable)
    {
        $this->version = $version;
        $this->blueprint = $blueprint;
        $this->indexModelTable = $indexModelTable;
    }

    public function testMigration(): array
    {
        if (! $this->blueprint) {
            $this->state = 'No Blueprint';
            $this->message = '';

            return $this->asArray();
        }
        if (! is_callable($this->blueprint)) {
            $this->state = 'Blueprint Error';
            $this->message = 'Blueprint is not callable';

            return $this->asArray();
        }

        $tempIndex = 'elasticlens_test_index_for_'.$this->indexModelTable;
        Schema::deleteIfExists($tempIndex);
        try {
            Schema::create($tempIndex, $this->blueprint);
            $this->indexMap = Schema::getMappings($tempIndex);
            Schema::deleteIfExists($tempIndex);
            $this->validated = true;
            $this->state = 'Validated';

            return $this->asArray();
        } catch (Exception $e) {
            $this->message = $e->getMessage();
            $this->state = 'Failed Migration Validation';

            return $this->asArray();
        }
    }

    private function asArray(): array
    {
        return [
            'version' => $this->version,
            'blueprint' => $this->blueprint,
            'validated' => $this->validated,
            'state' => $this->state,
            'message' => $this->message,
            'indexMap' => $this->indexMap,
        ];
    }
}
