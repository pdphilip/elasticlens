<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens;

use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\Builder\IndexBuilder;
use PDPhilip\ElasticLens\Eloquent\LensBuilder;
use PDPhilip\ElasticLens\Engine\BuildResult;
use PDPhilip\ElasticLens\Engine\RecordBuilder;
use PDPhilip\ElasticLens\Enums\IndexableBuildState;
use PDPhilip\ElasticLens\Enums\IndexableMigrationLogState;
use PDPhilip\ElasticLens\Index\LensState;
use PDPhilip\ElasticLens\Index\MigrationValidator;
use PDPhilip\ElasticLens\Models\IndexableBuild;
use PDPhilip\ElasticLens\Models\IndexableMigrationLog;
use PDPhilip\Elasticsearch\Eloquent\Builder;
use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * @property string $id
 *
 * @method static LensBuilder query()
 * @method Collection getBase($columns = ['*'])
 * @method \PDPhilip\Elasticsearch\Eloquent\ElasticCollection|array getIndex($columns = ['*'])
 * @method \Illuminate\Pagination\LengthAwarePaginator paginateBase(int $perPage = 15, string $pageName = 'page', ?int $page = null, array $options = [])
 * @method \Illuminate\Pagination\LengthAwarePaginator paginateIndex($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
 * @method static Collection asBase() This method should only be called on a Collection of index model results.
 *
 * @phpstan-consistent-constructor
 */
abstract class IndexModel extends Model
{
    public $connection = 'elasticsearch';

    protected $observeBase = true;

    protected $baseModel;

    protected int $migrationMajorVersion = 1;

    protected int $buildChunkRate = 0;

    protected ?bool $indexSoftDeletes = null;

    private static bool $collectionMacroRegistered = false;

    public function __construct()
    {
        parent::__construct();
        if (! $this->baseModel) {
            $this->baseModel = $this->guessBaseModelName();
        }
        $this->setConnection(config('elasticlens.database') ?? 'elasticsearch');
        self::registerCollectionMacro();
    }

    // ======================================================================
    // Builder
    // ======================================================================

    public function newEloquentBuilder($query): LensBuilder
    {
        return new LensBuilder($query);
    }

    private static function registerCollectionMacro(): void
    {
        if (self::$collectionMacroRegistered) {
            return;
        }
        self::$collectionMacroRegistered = true;

        Collection::macro('asBase', function () {
            if ($this->isEmpty()) {
                return collect();
            }

            return IndexModel::batchFetchBaseModels($this);
        });
    }

    /**
     * Batch-fetch base models for a collection of index results using a single whereIn query.
     */
    public static function batchFetchBaseModels(Collection $indexResults): Collection
    {
        if ($indexResults->isEmpty()) {
            return collect();
        }

        $baseModelClass = $indexResults->first()->getBaseModel();
        $ids = $indexResults->pluck('id')->all();
        $keyName = (new $baseModelClass)->getKeyName();
        $baseModels = $baseModelClass::whereIn($keyName, $ids)->get()->keyBy($keyName);

        return $indexResults->map(fn ($item) => $baseModels->get($item->id))->filter()->values();
    }

    // ======================================================================
    // Base Model
    // ======================================================================

    public function getBaseAttribute()
    {
        return $this->baseModel::find($this->id);
    }

    public function asBase()
    {
        return $this->base;
    }

    public function getBaseModel(): string
    {
        if (! $this->baseModel) {
            return $this->guessBaseModelName();
        }

        return $this->baseModel;
    }

    public function isBaseModelDefined(): bool
    {
        return ! empty($this->baseModel);
    }

    public function guessBaseModelName(): string
    {
        return str_replace('Indexes\Indexed', '', get_class($this));
    }

    // ======================================================================
    // Field Map
    // ======================================================================

    public function fieldMap(): IndexBuilder
    {
        return IndexBuilder::map($this->baseModel);
    }

    public function getFieldSet(): array
    {
        return $this->fieldMap()->getFieldMap();
    }

    public function getRelationships(): array
    {
        return $this->fieldMap()->getRelationships();
    }

    public function getObserverSet(): array
    {
        $base = null;
        if (! empty($this->observeBase)) {
            $base = $this->getBaseModel();
        }
        $embedded = $this->fieldMap()->getObservers();
        if ($embedded) {
            $embedded = $this->mapUpstreamEmbeds($embedded);
        }

        return [
            'base' => $base,
            'embedded' => $embedded,
        ];
    }

    public function getObservedModels(): array
    {
        $set = $this->getObserverSet();
        $embedded = $set['embedded'];
        $embeddedModels = [];
        if ($embedded) {
            foreach ($embedded as $embed) {
                if ($embed['observe']) {
                    $embeddedModels[] = $embed['model'];
                }
            }
        }

        return [
            'base' => $set['base'],
            'embedded' => $embeddedModels,
        ];
    }

    // ======================================================================
    // Build Index
    // ======================================================================

    public static function indexBuild($id, $source): BuildResult
    {
        return RecordBuilder::build(static::class, $id, $source);
    }

    public function indexRebuild($source): BuildResult
    {
        return RecordBuilder::build(static::class, $this->id, $source);
    }

    // ======================================================================
    // Migration
    // ======================================================================

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

    public function getBuildChunkRate(): int|bool
    {
        return $this->buildChunkRate > 0 ? $this->buildChunkRate : false;
    }

    public function getIndexSoftDeletes(): ?bool
    {
        return $this->indexSoftDeletes;
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

    // ======================================================================
    // Health & Status
    // ======================================================================

    public static function lensHealth(): array
    {
        $lens = new LensState(static::class);

        return $lens->healthCheck();
    }

    public static function whereIndexBuilds($byLatest = false): Builder
    {
        $indexModel = strtolower(class_basename(static::class));
        $query = IndexableBuild::query()->where('index_model', $indexModel);
        if ($byLatest) {
            $query->orderByDesc('created_at');
        }

        return $query;
    }

    public static function whereFailedIndexBuilds($byLatest = false): Builder
    {
        $indexModel = strtolower(class_basename(static::class));
        $query = IndexableBuild::query()->where('index_model', $indexModel)->where('state', IndexableBuildState::FAILED);
        if ($byLatest) {
            $query->orderByDesc('created_at');
        }

        return $query;
    }

    public static function whereMigrations($byLatest = false): Builder
    {
        $indexModel = strtolower(class_basename(static::class));
        $query = IndexableMigrationLog::query()->where('index_model', $indexModel);
        if ($byLatest) {
            $query->orderByDesc('created_at');
        }

        return $query;
    }

    public static function whereMigrationErrors($byLatest = false): Builder
    {
        $indexModel = strtolower(class_basename(static::class));
        $query = IndexableMigrationLog::query()->where('index_model', $indexModel)->where('state', IndexableMigrationLogState::FAILED);
        if ($byLatest) {
            $query->orderByDesc('created_at');
        }

        return $query;
    }

    // ======================================================================
    // Helpers
    // ======================================================================

    public function mapUpstreamEmbeds(array $embeds): array
    {
        foreach ($embeds as $i => $embed) {
            $embeds[$i] = $this->fetchUpstream($embed, $embeds);
        }

        return $embeds;
    }

    private function fetchUpstream(array $embed, array $embeds): array
    {
        if ($embed['model'] !== $this->baseModel) {
            foreach ($embeds as $em) {
                if ($em['relation'] === $embed['model']) {
                    $embed['upstream'] = $this->fetchUpstream($em, $embeds);
                }
            }
        }

        return $embed;
    }
}
