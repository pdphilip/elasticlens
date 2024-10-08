<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\Traits\IndexBaseModel;
use PDPhilip\ElasticLens\Traits\IndexFieldMap;
use PDPhilip\ElasticLens\Traits\IndexMigrationMap;
use PDPhilip\Elasticsearch\Eloquent\Builder;
use PDPhilip\Elasticsearch\Eloquent\Model;

/**
 * @property string $_id
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

    //@var string
    public $connection = 'elasticsearch';

    //@var bool
    protected $observeBase = true;

    //@var string
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
            $items = $this->get()->forPage($page, $perPage);
            $items = $items->map(function ($value) {
                //@phpstan-ignore-next-line
                return $value->base;
            });

            return new LengthAwarePaginator(
                $items,
                $this->count(),
                $perPage,
                $page,
                $options
            );
        });
        Builder::macro('getBase', function () {
            return $this->get()->map(function ($value) {
                //@phpstan-ignore-next-line
                return $value->base;
            });
        });
        Collection::macro('asBase', function () {
            return $this->map(function ($value) {
                return $value->base;
            });
        });
    }

    public function base(): BelongsTo
    {
        return $this->belongsTo($this->baseModel, '_id');
    }

    public function asBase()
    {
        return $this->base;

    }
}
