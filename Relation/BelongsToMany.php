<?php

namespace Pantheion\Model\Relation;

use Pantheion\Database\Query\Builder;
use Pantheion\Facade\Arr;
use Pantheion\Facade\Str;
use Pantheion\Facade\Inflection;
use Pantheion\Model\Model;
use Pantheion\Model\Collection;
use Pantheion\Database\Query\JoinBuilder;
use Pantheion\Facade\Table;

/**
 * Relation Builder class that represents
 * a BelongsToMany relation
 */
class BelongsToMany extends RelationBuilder
{
    /**
     * Pivot's table name
     *
     * @var string
     */
    protected $pivotTable;

    /**
     * Pivot's table instance
     *
     * @var \Pantheion\Database\Table\Table
     */
    protected $pivotTableInstance;

    /**
     * BelongsToMany constructor function
     *
     * @param Model $instance
     * @param string $other
     * @param string $pivotTable
     */
    public function __construct(
        Model $instance, 
        string $other, 
        string $pivotTable = null
    ) {
        $this->pivotTable = $pivotTable ?: $this->generatePivotTable($instance, $other);
        $this->pivotTableInstance = Table::use($this->pivotTable);

        parent::__construct($instance, $other);
    }

    /**
     * Formats a table name for the pivot
     * based on the other table's name
     *
     * @return string
     */
    protected function generatePivotTable(Model $instance, string $other)
    {
        $tables = Arr::sort([
            Inflection::singularize($instance->table()),
            Inflection::singularize($this->formatTable($other))
        ]);
        
        return join("_", $tables);
    }

    /**
     * Overrides the get method to
     * also return the pivot data
     *
     * @return void
     */
    public function get()
    {
        $results = $this->callOriginalGet();

        $models = [];
        foreach ($results as $entry) {
            $attributes = $this->getModelAttributes($entry);
            $attributes["pivot"] = $this->getPivotAttributes($entry);
            
            $models[] = (new $this->class)->setAttributes($attributes, true);;
        }

        return new Collection($this->class, $models);
    }

    /**
     * Calls the original get() method from the
     * original Builder class
     *
     * @return array
     */
    protected function callOriginalGet()
    {
        $class = get_parent_class(get_parent_class(get_parent_class($this)));

        return (new \ReflectionMethod($class, 'get'))->invoke($this);
    }

    /**
     * Transforms an array of raw attributes
     * from the database into an array
     * of type cast attributes for the Model
     *
     * @param array $entry raw attributes
     * @return array
     */
    protected function getModelAttributes(array $entry)
    {
        $attributes = [];

        foreach ($entry as $key => $value) {
            if ($this->tableInstance->hasColumn($key)) {
                $attributes[$key] = $this->tableInstance
                    ->schema
                    ->getColumn($key)
                    ->type
                    ->toCodeValue($value);
            }
        }

        return $attributes;
    }

    /**
     * Transforms an array of raw attributes
     * from the database into an array
     * of type cast attributes for the pivot
     *
     * @param array $entry raw attributes
     * @return array
     */
    protected function getPivotAttributes(array $entry)
    {   
        $rawAttributes = $this->filterPivotAttributes($entry);

        $attributes = [];
        foreach ($rawAttributes as $key => $value) {
            $keyCorrected = Str::replaceFirst("{$this->pivotTable}_", "", $key);

            if ($this->pivotTableInstance->hasColumn($keyCorrected)) {
                $attributes[$keyCorrected] = $this->pivotTableInstance
                    ->schema
                    ->getColumn($keyCorrected)
                    ->type
                    ->toCodeValue($value);
            }
        }

        return $attributes;
    }

    /**
     * Returns only the attributes that
     * will belong to the pivot
     *
     * @param array $entry result attributes
     * @return array
     */
    protected function filterPivotAttributes(array $entry)
    {
        $clone = clone $this;
        return array_filter($entry, function($value, $key) use ($clone) {
            return Str::startsWith($key, $clone->pivotTable);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Prepares the query for the relation
     *
     * @return void
     */
    protected function prepare()
    {
        $clone = clone $this;

        $this->join($this->pivotTable, function(JoinBuilder $query) use ($clone) {
            $query
                ->on($clone->getAttributeToSelect(), 'id')
                ->select($clone->getAttribute(), $clone->getAttributeToSelect())
                ->where($clone->getAttribute(), $clone->getValue());
        })
        ->whereInSelect('id', $this->pivotTable, function(Builder $query) use ($clone) {
            $query
                ->select($clone->getAttributeToSelect())
                ->where($clone->getAttribute(), $clone->getValue());
        });
    }

    protected function getAttributeToSelect()
    {
        return Inflection::singularize($this->table) . "_id";
    }

    /**
     * Returns the attribute to apply
     * the relation
     *
     * @return string
     */
    protected function getAttribute()
    {
        return Inflection::singularize($this->originalModelTable) . "_id";
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

    public function using(string $class)
    {

    }

    /**
     * Adds more columns to the pivot
     * selection
     *
     * @param ...$columns
     * @return BelongsToMany
     */
    public function with(...$columns)
    {
        foreach($this->joins as $join) {
            if($join["table"] === $this->pivotTable) {
                foreach($columns as $column) {
                    $join["join"]
                        ->columns[$this->pivotTable][] = [
                            'name' => $column, 
                            'alias' => "{$this->pivotTable}_{$column}"
                        ];
                }
            }
        }

        return $this;
    }
}
