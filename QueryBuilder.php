<?php

namespace Pantheion\Model;

use Pantheion\Database\Query\Builder;
use Pantheion\Facade\Table;
use Pantheion\Database\Table\Table as TableInstance;

/**
 * Layer class on top of the Builder
 * that gets the results ready for
 * the Model
 */
class QueryBuilder extends Builder
{
    /**
     * Instance of the Table used
     * for the query
     *
     * @var TableInstance
     */
    protected $tableInstance;

    /**
     * Model's class
     *
     * @var string
     */
    protected $class;

    /**
     * QueryBuilder's constructor function.
     * It constructs the base class and
     * then initializes the "$tableInstance"
     *
     * @param string $table table's name
     * @param string $class model's class
     */
    public function __construct(string $table, string $class)
    {
        parent::__construct($table);
        $this->class = $class;
        $this->tableInstance = Table::use($table);
    }

    /**
     * Inserts the new model with
     * its attributes and returns a full
     * array of attributes including
     * its new 'id' attribute
     *
     * @param array $attributes
     * @return array
     */
    public function insert(array $attributes)
    {
        $id = parent::insert($attributes);

        $modelAttributes = [];
        foreach($this->tableInstance->schema->columns as $column)
        {
            $modelAttributes[$column->name] = 
                array_key_exists($column->name, $attributes) ? 
                $attributes[$column->name] : 
                null;
        }

        $modelAttributes['id'] = intval($id);
        
        return $modelAttributes;
    }

    /**
     * Returns a Collection with
     * the results of the query
     *
     * @return Collection
     */
    public function get()
    {
        $results = parent::get();

        $models = [];
        foreach($results as $result) {
            $attributes = [];

            foreach($result as $key => $value) {
                if($this->tableInstance->hasColumn($key)) {
                    $attributes[$key] = $this->tableInstance
                        ->schema
                        ->getColumn($key)
                        ->type
                        ->toCodeValue($value);
                }
            }

            $models[] = (new $this->class)->setAttributes($attributes, true);
        }

        return new Collection($this->class, $models);
    }

    /**
     * Retrieves the first result
     * from the query
     *
     * @return Model
     */
    public function first()
    {
        $this->limit(1);
        $result = $this->get();
        // ******************************** TODO
        // $result = parent::get(); 

        return !$result->empty() ? $result->first() : null;
    }

    /**
     * Retrieves the first result
     * from the query or throws
     * an Exception
     *
     * @return Model
     */
    public function firstOrFail()
    {
        $result = $this->first();

        if(is_null($result)) {
            throw new \Exception("Model not found");
        }
    }

    /**
     * Returns the amount of results
     * that resulted from the query
     * 
     * @return int
     */
    public function count()
    {
        $this->columns = ["COUNT(*) as count"];

        return intval(parent::get()[0]["count"]);
    }

    /**
     * Returns the average of a column
     * from the results of the query.
     * The result is in a string format
     * 
     * @param string $column column to perform the average
     * @return mixed
     */
    public function avg(string $column)
    {
        $this->columns = [sprintf("AVG(`%s`) as avg", $column)];

        return $this->getCodeValue($column, parent::get()[0]["avg"]);
    }

    /**
     * Returns the min value from a column
     * of the results of the query. The result
     * is in a string format.
     * 
     * @param string $column column to get the min value
     * @return mixed
     */
    public function min(string $column)
    {
        $this->columns = [
            sprintf("MIN(`%s`) as min", $column)
        ];

        return $this->getCodeValue($column, parent::get()[0]["min"]);
    }

    /**
     * Returns the max value from a column
     * of the results of the query. The result
     * is in a string format.
     * 
     * @param string $column column to get the max value
     * @return string
     */
    public function max(string $column)
    {
        $this->columns = [
            sprintf("MAX(`%s`) as max", $column)
        ];

        return $this->getCodeValue($column, parent::get()[0]["max"]);
    }

    /**
     * Returns the values from a certain
     * column from the results of the query
     * 
     * @param string $column
     * @return array
     */
    public function pluck(string $column)
    {
        $this->select($column);
        $results = array_column(parent::get(), $column);

        $copy = clone $this;
        return array_map(function($result) use ($copy, $column) {
            return $copy->getCodeValue($column, $result);
        }, $results);
    }

    /**
     * Returns a raw string value cast into
     * its true type
     *
     * @param string $column
     * @param string $raw
     * @return void
     */
    protected function getCodeValue(string $column, string $raw)
    {
        return $this->tableInstance
            ->schema
            ->getColumn($column)
            ->type
            ->toCodeValue($raw);
    }
}