<?php

namespace Pantheion\Model\Relation;

use Pantheion\Facade\Inflection;
use Pantheion\Model\Model;

/**
 * Relation Builder class that represents
 * a BelongsTo relation
 */
class BelongsTo extends RelationBuilder
{
    /**
     * Overrides the get method
     * to retrieve only the first
     *
     * @return Model
     */
    public function get()
    {
        return parent::get()->first();
    }

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
        return 'id';
    }

    /**
     * Returns the value to apply
     * the relation
     *
     * @return mixed
     */
    protected function getValue()
    {
        $foreignKey = Inflection::singularize($this->table) . "_id";
        return $this->originalModelInstance->$foreignKey;
    }
}