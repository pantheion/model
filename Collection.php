<?php

namespace Pantheion\Model;

use Pantheion\Facade\Arr;

/**
 * Represents a group of Models
 * return from a query
 */
class Collection implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * Collection's models class
     *
     * @var string
     */
    protected $class;

    /**
     * Array of models
     *
     * @var array
     */
    protected $items;

    /**
     * Collection constructor function
     *
     * @param string $class
     * @param array $items
     */
    public function __construct(string $class, array $items = [])
    {
        $this->class = $class;
        $this->items = $items;
    }

    /**
     * Checks if the collection has
     * any items
     *
     * @return boolean
     */
    public function empty()
    {
        return Arr::empty($this->items);
    }

    /**
     * Returns the first item of
     * the Collection
     *
     * @return mixed
     */
    public function first()
    {
        return Arr::first($this->items);
    }

    /**
     * Returns an array
     * with the values of the
     * specified column from all
     * the items
     *
     * @param string $column
     * @return array
     */
    public function pluck(string $column)
    {
        return array_column($this->items, $column);
    }

    // ----------------------------------------------------------------------------------

    /**
     * Returns the items to be
     * enconded in JSON string
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->items;
    }

    /**
     * Whether a offset exists
     * 
     * @param mixed $offset
     * @return boolean 
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * Offset to retrieve
     * 
     * @param mixed $offset
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    /**
     * Offset to set
     * 
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * 
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * Retrieve an external iterator
     * 
     * @return Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Count elements of an object
     * 
     * @return int 
     */
    public function count()
    {
        return count($this->items);
    }
}
