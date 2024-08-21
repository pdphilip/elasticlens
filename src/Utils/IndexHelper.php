<?php

namespace PDPhilip\ElasticLens\Utils;

use PDPhilip\Elasticsearch\Schema\Schema;
use PDPhilip\ElasticLens\Index\LensIndex;

class IndexHelper extends LensIndex
{
    public function indexMapping(): array
    {
        $mappings = Schema::getMappings($this->indexModelTable);
        $mappings = reset($mappings);
        $schema = [];
        if (! empty($mappings['mappings']['properties'])) {

            $mappings = $mappings['mappings']['properties'];
            if (! empty($mappings['_meta']['properties']['_id'])) {
                $_id = $mappings['_meta']['properties']['_id'];

                $schema['_id'] = $_id;

            } else {
                $schema['_id'] = [
                    'type' => 'text',
                ];
            }

            unset($mappings['_meta']);

            foreach ($mappings as $field => $mapping) {
                $schema[$field] = $mapping;
            }

        }

        return $schema;
    }

    public function indexFields($sorted = false, $excludeEmbedded = false): array
    {
        $schema = $this->indexMapping();
        if (! $schema) {
            return [];
        }

        $maps = $this->_mapProperties($schema);
        if ($sorted) {
            $maps = $this->_reasonableMapSort($maps, $excludeEmbedded);
        }

        return $maps;
    }

    private function _mapField($val, $field): array
    {
        $maps = [];
        if (isset($val['properties'])) {
            return $this->_mapProperties($val['properties'], $field);

        }
        $maps[$field] = $val['type'];

        return $maps;
    }

    private function _mapProperties($properties, $parentField = null): array
    {
        $propertyMaps = [];
        foreach ($properties as $field => $value) {
            if ($parentField) {
                $field = $parentField.'.'.$field;
            }
            $maps = $this->_mapField($value, $field);

            foreach ($maps as $f => $type) {
                $propertyMaps[$f] = $type;
            }
        }

        return $propertyMaps;
    }

    private function _reasonableMapSort($maps, $excludeEmbedded): array
    {
        $sorted = [];
        $commonFields = [
            '_id',
            'name',
            'email',
            'first_name',
            'last_name',
            'type',
            'state',
            'status',
            'created_at',
            'updated_at',
        ];
        foreach ($commonFields as $field) {
            if (isset($maps[$field])) {
                $sorted[$field] = $maps[$field];
                unset($maps[$field]);
            }
        }
        $embedded = [];
        foreach ($maps as $field => $type) {
            if (str_contains($field, '.')) {
                $embedded[$field] = $type;
                unset($maps[$field]);

                continue;
            }
            $sorted[$field] = $type;
        }
        if (! $excludeEmbedded) {
            if (! empty($embedded)) {
                foreach ($embedded as $field => $type) {
                    $sorted[$field] = $type;
                }
            }
        }

        return $sorted;
    }
}
