<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Eloquent;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PDPhilip\ElasticLens\IndexModel;
use PDPhilip\Elasticsearch\Eloquent\Builder;
use PDPhilip\Elasticsearch\Eloquent\ElasticCollection;

class LensBuilder extends Builder
{
    protected bool $returnAsBase = false;

    public function returnAsBase(): static
    {
        $this->returnAsBase = true;

        return $this;
    }

    // ======================================================================
    // Get
    // ======================================================================

    /**
     * When returnAsBase is set, returns base (Laravel) models.
     * Otherwise returns index models (default ES behavior).
     *
     * @param  array|string  $columns
     */
    public function get($columns = ['*']): ElasticCollection|array
    {
        $results = parent::get($columns);

        if ($this->returnAsBase) {
            return IndexModel::batchFetchBaseModels($results); // @phpstan-ignore return.type
        }

        return $results;
    }

    /**
     * Always returns index models, regardless of scope flag.
     *
     * @param  array|string  $columns
     */
    public function getIndex($columns = ['*']): ElasticCollection|array
    {
        return parent::get($columns);
    }

    /**
     * Always returns base (Laravel) models, regardless of scope flag.
     *
     * @param  array|string  $columns
     */
    public function getBase($columns = ['*']): Collection
    {
        $results = parent::get($columns);

        if ($results->isEmpty()) {
            return collect();
        }

        return IndexModel::batchFetchBaseModels($results);
    }

    // ======================================================================
    // Paginate
    // ======================================================================

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        if (! $this->returnAsBase) {
            return parent::paginate($perPage, $columns, $pageName, $page, $total);
        }

        return $this->paginateBase($perPage ?? 15, $pageName, $page);
    }

    /**
     * Always returns a paginator of index models, regardless of scope flag.
     */
    public function paginateIndex($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null): LengthAwarePaginator
    {
        return parent::paginate($perPage, $columns, $pageName, $page, $total);
    }

    /**
     * Always returns a paginator of base (Laravel) models, regardless of scope flag.
     */
    public function paginateBase(int $perPage = 15, string $pageName = 'page', ?int $page = null, array $options = []): LengthAwarePaginator
    {
        $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
        $path = LengthAwarePaginator::resolveCurrentPath();

        $esResults = parent::paginate($perPage, ['*'], $pageName, $page);
        $items = collect($esResults->items());

        if ($items->isEmpty()) {
            return new LengthAwarePaginator(collect(), 0, $perPage, $page, $options + ['path' => $path]);
        }

        $baseItems = IndexModel::batchFetchBaseModels($items);

        $paginator = new LengthAwarePaginator($baseItems, $esResults->total(), $perPage, $page, $options);
        $paginator->setPath($path);

        return $paginator;
    }
}
