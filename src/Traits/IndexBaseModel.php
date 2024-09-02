<?php

namespace PDPhilip\ElasticLens\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait IndexBaseModel
{
    public function guessBaseModelName()
    {
        $baseTable = $this->getTable();
        $prefix = DB::connection(config('elasticlens.database'))->getConfig('index_prefix');
        if ($prefix) {
            $baseTable = str_replace($prefix.'_', '', $baseTable);
        }

        $baseTable = str_replace('indexed_', '', $baseTable);
        $baseModel = Str::singular($baseTable);

        $baseModel = Str::studly($baseModel);

        return config('elasticlens.namespaces.models').'\\'.$baseModel;
    }

    public function getBaseModel()
    {
        if (! $this->baseModel) {
            return $this->guessBaseModelName();
        }

        return $this->baseModel;
    }

    public function isBaseModelDefined()
    {
        return ! empty($this->baseModel);
    }
}
