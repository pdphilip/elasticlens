<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\Enums\IndexableBuildState;
use PDPhilip\ElasticLens\Enums\IndexableMigrationLogState;
use PDPhilip\ElasticLens\Index\LensState;
use PDPhilip\ElasticLens\Models\IndexableBuild;
use PDPhilip\ElasticLens\Models\IndexableMigrationLog;
use PDPhilip\ElasticLens\Traits\IndexBaseModel;
use PDPhilip\ElasticLens\Traits\IndexFieldMap;
use PDPhilip\ElasticLens\Traits\IndexMigrationMap;
use PDPhilip\Elasticsearch\Eloquent\Builder;
use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * @property string $id
 *
 * @method static $this get()
 * @method static $this search()
 * @method static Collection getBase()
 * @method static Collection asBase() This method should only be called after get() or search() has been executed.
 * @method static LengthAwarePaginator paginateBase($perPage = 15, $pageName = 'page', $page = null, $options = [])
 *                                                                                                                  *
 * @phpstan-consistent-constructor
 */
abstract class IndexModel extends Model
{
    use IndexBaseModel, IndexFieldMap, IndexMigrationMap;

    // @var string
    public $connection = 'elasticsearch';

    // @var bool
    protected $observeBase = true;

    // @var string
    protected $baseModel;

    public function __construct()
    {
        parent::__construct();
        if (! $this->baseModel) {
            $this->baseModel = $this->guessBaseModelName();
        }
        $this->setConnection(config('elasticlens.database') ?? 'elasticsearch');
        Builder::macro('paginateBase', function ($perPage = 15, $pageName = 'page', $page = null, $options = []) {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
            $path = LengthAwarePaginator::resolveCurrentPath();
            $items = $this->get()->forPage($page, $perPage);
            $items = $items->map(function ($value) {
                // @phpstan-ignore-next-line
                return $value->base;
            });
            $pagi = new LengthAwarePaginator(
                $items,
                $this->count(),
                $perPage,
                $page,
                $options
            );
            $pagi->setPath($path);

            return $pagi;
        });
        Builder::macro('getBase', function () {
            return $this->get()->map(function ($value) {
                // @phpstan-ignore-next-line
                return $value->base;
            });
        });
        Collection::macro('asBase', function () {
            return $this->map(function ($value) {
                return $value->base;
            });
        });
    }

    public function getBaseAttribute()
    {
        return $this->baseModel::find($this->id);
    }

    public function asBase()
    {
        return $this->base;
    }

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
}
