<?php

namespace Pantheion\Model\Relation;

use Pantheion\Facade\Inflection;

/**
 * Relation Builder class that represents
 * a HasMany relation
 */
class HasMany extends RelationBuilder
{
    /**
     * Prepares the query for the relation
     *
     * @return void
     */
    protected function prepare()
    {
        $this->where($this->getAttribute(), $this->getValue());
    }

    /**
     * Returns the attribute to apply
     * the relation
     *
     * @return string
     */
    protected function getAttribute()
    {
        return Inflection::singularize($this->originalModelInstance->table()) . "_id";
    }

    /**
     * Returns the value to apply
     * the relation
     *
     * @return mixed
     */
    protected function getValue()
    {
        return $this->originalModelInstance->id;
    }
}
