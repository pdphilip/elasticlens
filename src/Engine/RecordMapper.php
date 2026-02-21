<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Engine;

use Carbon\Carbon;
use PDPhilip\ElasticLens\Config\IndexConfig;

class RecordMapper
{
    public static function map($model, IndexConfig $config): ?array
    {
        if ($model->excludeIndex()) {
            return null;
        }

        $data = ['id' => $model->{$model->getKeyName()}];
        if (isset($config->fieldMap['id'])) {
            $data['id'] = self::castType($data['id'], $config->fieldMap['id']);
        }

        $fieldData = self::mapFields($config->fieldMap, $model, $config);
        $data = $data + $fieldData;

        return $data;
    }

    private static function mapFields(array $fields, $modelData, IndexConfig $config): array
    {
        $data = [];
        if (! $fields) {
            // No field map — take all attributes from the model
            $data = $modelData->toArray();
            if ($modelData instanceof $config->baseModel) {
                unset($data[$config->baseModelPrimaryKey]);
            }

            return $data;
        }

        foreach ($fields as $field => $type) {
            if ($field === 'id') {
                continue;
            }

            if (is_array($type)) {
                $data[$field] = self::mapEmbeddedRelationship($field, $type, $modelData, $config);

                continue;
            }

            $value = $modelData->{$field} ?? null;
            if ($value) {
                $value = self::castType($value, $type);
            }
            $data[$field] = $value;
        }

        return $data;
    }

    private static function mapEmbeddedRelationship(string $field, array $embedFields, $parentData, IndexConfig $config): array
    {
        $relationships = $config->relationships;
        if (empty($relationships[$field])) {
            return [];
        }

        $relationship = $relationships[$field];
        $type = $relationship['type'];
        $relation = $relationship['relation'];
        $whereRelatedField = $relationship['whereRelatedField'];
        $equalsModelField = $relationship['equalsModelField'];
        $modelFieldValue = $parentData->{$equalsModelField};
        $query = $relationship['query'];

        $records = $relation::where($whereRelatedField, $modelFieldValue);
        if ($query) {
            $records = $records->tap($query);
        }

        if ($type === 'hasMany') {
            $records = $records->get();
            $data = [];
            foreach ($records as $record) {
                $data[] = self::mapFields($embedFields, $record, $config);
            }

            return $data;
        }

        $record = $records->first();
        if (! $record) {
            return [];
        }

        return self::mapFields($embedFields, $record, $config);
    }

    private static function castType(mixed $value, string $type): mixed
    {
        if ($type === Carbon::class) {
            return Carbon::create($value);
        }

        if (enum_exists($type)) {
            $value = $value->value ?? $value;
            $type = 'string';
        }

        settype($value, $type);

        return $value;
    }
}
