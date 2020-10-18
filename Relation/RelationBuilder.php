<?php

namespace Pantheion\Model\Relation;

use Pantheion\Model\QueryBuilder;
use Pantheion\Model\Model;
use Pantheion\Facade\Inflection;

/**
 * Query Builder class to apply
 * for table relations
 */
abstract class RelationBuilder extends QueryBuilder
{
    /**
     * Instance to which the relation
     * query is gonna apply
     *
     * @var Model
     */
    protected $originalModelInstance;

    /**
     * Class of the original Model 
     *
     * @var string
     */
    protected $originalModelClass;

    /**
     * Class of the original Model 
     *
     * @var string
     */
    protected $originalModelTable;

    /**
     * RelationBuilder constructor function 
     *
     * @param Model $instance instance to apply the relation
     * @param string $other Model class name to relate to
     */
    public function __construct(Model $instance, string $other)
    {
        $this->originalModelInstance = $instance;
        $this->originalModelClass = get_class($instance);
        $this->originalModelTable = $instance->table();
        
        parent::__construct($this->formatTable($other), $other);
        
        $this->prepare();
    }

    /**
     * Returns the table name of the
     * Model to be related to
     *
     * @param string $class 
     * @return string
     */
    protected function formatTable(string $class)
    {
        return Inflection::tablerize(
            (new \ReflectionClass(new $class))->getShortName()
        );
    }

    /**
     * Prepares the query for the relation
     *
     * @return void
     */
    protected abstract function prepare();

    /**
     * Returns the attribute to apply
     * the relation
     *
     * @return string
     */
    protected abstract function getAttribute();

    /**
     * Returns the value to apply
     * the relation
     *
     * @return mixed
     */
    protected abstract function getValue();
}