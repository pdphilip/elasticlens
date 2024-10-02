<?php

namespace PDPhilip\ElasticLens;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PDPhilip\Elasticsearch\Eloquent\Builder;

/**
 * @method static $this get()
 * @method static $this search()
 * @method static Collection getBase()
 * @method static Collection asBase() This method should only be called after get() or search() has been executed.
 * @method static LengthAwarePaginator paginateBase($perPage = 15, $pageName = 'page', $page = null, $options = [])
 *
 * @return Builder
 */
class QueryBuilder extends Builder {}
