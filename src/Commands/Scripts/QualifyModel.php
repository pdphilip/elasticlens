<?php

namespace PDPhilip\ElasticLens\Commands\Scripts;

class QualifyModel
{
    public static function check($model)
    {
        $modelNameSpaces = config('elasticlens.namespaces');
        $found = null;
        $notFound = [];
        foreach ($modelNameSpaces as $modelNameSpace => $indexNameSpace) {
            $modelPath = $modelNameSpace.'\\'.$model;
            if (class_exists($modelPath)) {
                $found = $modelPath;
            } else {
                $notFound[] = $modelPath;
            }
        }

        return [
            'qualified' => $found,
            'notFound' => $notFound,
        ];
    }
}
