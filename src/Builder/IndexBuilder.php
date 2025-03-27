<?php

declare(strict_types=1);

namespace PDPhilip\ElasticLens\Builder;

use Illuminate\Support\Str;
use RuntimeException;

class IndexBuilder
{
    protected mixed $model;

    protected mixed $embedFieldName;

    protected mixed $parentMap;

    protected mixed $baseMap;

    protected mixed $fields = [];

    protected array $observers = [];

    protected array $relationships = [];

    // Entry point for defining the map
    public static function map($model, $callback = null): IndexBuilder
    {
        $mapper = new self;
        $mapper->for($model);
        $mapper->setBaseMap($mapper);
        if (is_callable($callback)) {
            $callback(new IndexField($mapper));
        }

        return $mapper;
    }

    public function getFieldMap()
    {
        return $this->fields;
    }

    public function getObservers()
    {
        return $this->baseMap->observers;
    }

    public function getRelationships()
    {
        return $this->baseMap->relationships;
    }

    public function for($model): static
    {
        $this->model = $model;

        return $this;
    }

    public function addField($field, $type): static
    {
        $this->fields[$field] = $type;

        return $this;
    }

    public function setEmbedField($field): static
    {
        $this->embedFieldName = $field;

        return $this;
    }

    public function setParentMap($parent): static
    {
        $this->parentMap = $parent;

        return $this;
    }

    public function setBaseMap($builder): static
    {
        $this->baseMap = $builder;

        return $this;
    }

    public function setRelationship($field, $relationship): static
    {
        $this->baseMap->relationships[$field] = $relationship;

        return $this;
    }

    public function getBaseMap()
    {
        return $this->baseMap;
    }

    // ----------------------------------------------------------------------
    // Embedded Methods
    // ----------------------------------------------------------------------

    public function isEmbedded(): bool
    {
        return ! empty($this->embedFieldName) && $this->parentMap;
    }

    // Add an embedded relationship to the map
    public function addEmbed($field, $relation, $type, $whereRelatedField = null, $equalsLocalField = null, $query = null): IndexBuilder
    {
        $current = $this;

        $mapper = new self;
        $mapper->for($relation);
        $mapper->setEmbedField($field);
        $mapper->setParentMap($current);
        $mapper->setBaseMap($current->getBaseMap());
        $relationship = $mapper->buildRelationship($mapper->parentMap->model, $relation, $type, $whereRelatedField, $equalsLocalField, $query);
        $mapper->attachEmbeddedObserver($relationship);
        $mapper->setRelationship($field, $relationship);

        return $mapper;
    }

    // Embed map for related models
    public function embedMap($callback): self
    {
        if (! $this->isEmbedded()) {
            throw new RuntimeException('Embedded Maps can only be called for embedded fields');
        }

        if (is_callable($callback)) {
            $callback(new IndexField($this));
        }

        $this->parentMap->addField($this->embedFieldName, $this->fields);

        return $this;
    }

    public function attachEmbeddedObserver($relationship): void
    {
        $this->baseMap->_attachObserver($relationship);

    }

    public function attachBaseObserver(): void
    {
        $relationship = $this->buildRelationship($this->model, $this->model, 'base');
        $this->baseMap->_attachObserver($relationship);
    }

    public function dontObserve(): self
    {
        if (! $this->isEmbedded()) {
            return $this;
        }
        $observers = $this->baseMap->observers;
        $observer = array_pop($observers);
        $observer['observe'] = false;
        $observers[] = $observer;
        $this->baseMap->observers = $observers;

        return $this;
    }

    public function _attachObserver($relationship): void
    {
        $observers = $this->baseMap->observers;
        $observers[] = $relationship;

        $this->baseMap->observers = $observers;
    }

    protected function buildRelationship($baseModel, $relation, $type, $whereRelatedField = null, $equalsLocalField = null, $query = null): array
    {
        if (! $whereRelatedField || $equalsLocalField) {
            [$whereRelatedField, $equalsLocalField] = $this->_inferKeys($baseModel, $relation, $type, $whereRelatedField, $equalsLocalField);
        }

        return [
            'observe' => true,
            'model' => $baseModel,
            'type' => $type,
            'relation' => $relation,
            'whereRelatedField' => $whereRelatedField,
            'equalsModelField' => $equalsLocalField,
            'query' => $query,
        ];
    }

    private function _inferKeys($baseModel, $relation, $type, $foreignKey = null, $localKey = null): array
    {

        if ($type === 'base') {
            $base = (new $baseModel);
            $id = $base->getKeyName();
            $foreignKey = $id;
            $localKey = $id;
        }

        if ($type == 'belongsTo') {
            if (! $foreignKey) {
                $base = (new $baseModel);
                $foreignKey = $base->getKeyName();
            }
            if (! $localKey) {
                $rel = (new $relation);
                $table = $rel->getTable();
                $localKey = Str::singular($table).'_id';
            }

        }

        if (in_array($type, ['hasMany', 'hasOne'])) {
            if (! $foreignKey) {
                $base = (new $baseModel);
                $table = $base->getTable();
                $foreignKey = Str::singular($table).'_id';

            }
            if (! $localKey) {
                $rel = (new $relation);
                $localKey = $rel->getKeyName();
            }

        }

        return [$foreignKey, $localKey];
    }
}
