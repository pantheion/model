<?php

namespace Pantheion\Model\Relation;

use Pantheion\Database\Query\Builder;
use Pantheion\Facade\Inflection;
use Pantheion\Model\Model;

/**
 * Relation Builder class that represents
 * a BelongsTo relation
 */
class HasOneThrough extends RelationBuilder
{
    /**
     * Through table's class
     *
     * @var string
     */
    protected $throughClass;

    /**
     * Through table's name
     *
     * @var string
     */
    protected $throughTable;

    /**
     * HasOneThrough constructor function
     *
     * @param Model $instance
     * @param string $other
     * @param string $through
     */
    public function __construct(Model $instance, string $other, string $through)
    {
        $this->throughClass = $through;
        $this->throughTable = $this->getThroughTable();
        parent::__construct($instance, $other);
    }

    /**
     * Returns the table name of the
     * Model to be related to
     *
     * @return string
     */
    protected function getThroughTable()
    {
        return Inflection::tablerize(
            (new \ReflectionClass(new $this->throughClass))->getShortName()
        );
    }

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
        $clone = clone $this;

        $this->whereInSelect(
            $this->getAttribute(), 
            $this->throughTable, 
            function(Builder $builder) use ($clone) {
                $builder
                    ->select($clone->getThroughSelect())
                    ->where($clone->getThroughWhereColumn(), $clone->getValue());
            }
        );
    }

    /**
     * Returns the column to select in
     * the Where-In-Select clause which
     * represents the through aspect of the
     * relation
     *
     * @return string
     */
    protected function getThroughSelect()
    {
        return Inflection::singularize($this->table) . "_id";
    }

    /**
     * Returns the "where column" in
     * the Where-In-Select clause which
     * represents the through aspect of the
     * relation
     *
     * @return string
     */
    protected function getThroughWhereColumn()
    {
        return "id";
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
        $throughAttribute = Inflection::singularize($this->throughTable) . "_id";
        return $this->originalModelInstance->$throughAttribute;
    }
}
