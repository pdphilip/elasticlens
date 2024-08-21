<?php

namespace PDPhilip\ElasticLens\Builder;

class IndexField
{
    protected IndexBuilder $mapper;

    public function __construct($mapper)
    {
        $this->mapper = $mapper;
    }

    //----------------------------------------------------------------------
    // Field sets
    //----------------------------------------------------------------------

    public function text($field): IndexField|static
    {
        return $this->addField($field, 'string');
    }

    public function integer($field): IndexField|static
    {
        return $this->addField($field, 'integer');
    }

    public function array($field): IndexField|static
    {
        return $this->addField($field, 'array');
    }

    public function bool($field): IndexField|static
    {
        return $this->addField($field, 'bool');
    }

    public function type($field, $type): IndexField|static
    {
        return $this->addField($field, $type);
    }

    //----------------------------------------------------------------------
    // Embeds
    //----------------------------------------------------------------------

    public function embedsMany($field, $relation, $whereRelatedField = null, $equalsLocalField = null, $query = null)
    {
        return $this->addEmbed($field, $relation, 'hasMany', $whereRelatedField, $equalsLocalField, $query);
    }

    public function embedsOne($field, $relation, $whereRelatedField = null, $equalsLocalField = null, $query = null)
    {
        return $this->addEmbed($field, $relation, 'hasOne', $whereRelatedField, $equalsLocalField, $query);
    }

    public function embedsBelongTo($field, $relation, $whereRelatedField = null, $equalsLocalField = null, $query = null)
    {
        return $this->addEmbed($field, $relation, 'belongsTo', $whereRelatedField, $equalsLocalField, $query);
    }

    //----------------------------------------------------------------------
    // Builder entry points
    //----------------------------------------------------------------------

    protected function addField($field, $type): static
    {
        $this->mapper->addField($field, $type);

        return $this;
    }

    protected function addEmbed($field, $relation, $type, $whereRelatedField = null, $equalsLocalField = null, $query = null): IndexBuilder
    {
        return $this->mapper->addEmbed($field, $relation, $type, $whereRelatedField, $equalsLocalField, $query);

    }
}
