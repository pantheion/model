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
     * @var Model[]
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
     * Returns the items in the collection
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    public function avg(string $key)
    {

    }

    /**
     * Returns an array with the
     * items in chunks defined by
     * the size in the parameters
     *
     * @param integer $size chunk size
     * @return array
     */
    public function chunk(int $size)
    {
        return array_chunk($this->items, $size);
    }

    /**
     * Returns the Collection's Model class
     *
     * @return string
     */
    public function class()
    {
        return $this->class;
    }

    /**
     * Adds an array of items to the collection
     *
     * @param array $items items to concatenate
     * @return Collection
     */
    public function concat(array $items)
    {
        $this->items = Arr::merge($this->items, $items);
        return $this;
    }

    /**
     * Checks if the collection has
     * any model with the key value pair
     * passed as arguments
     *
     * @param string $key attribute name
     * @param mixed $value attribute value
     * @return boolean
     */
    public function contains(string $key, $value)
    {
        foreach($this->items as $item) {
            if($item->hasAttribute($key) && $item->$key == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strictly checks if the collection has
     * any model with the key value pair
     * passed as arguments
     *
     * @param string $key attribute name
     * @param mixed $value attribute value
     * @return boolean
     */
    public function containsStrict(string $key, $value)
    {
        foreach ($this->items as $item) {
            if ($item->hasAttribute($key) && $item->$key === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dies and dumps the collection
     *
     * @return void
     */
    public function dd()
    {
        dd($this);
    }

    /**
     * Returns the difference of Collections
     * based on the Model's primary key
     *
     * @param Collection $other other collection
     * @param string $key primary key attribute
     * @return Collection
     */
    public function diff(Collection $other, string $key = 'id')
    {
        $otherIds = $other->pluck($key);

        return new Collection(
            $this->class,
            array_filter($this->items, function($item) use ($key, $otherIds) {
                return !in_array($item->$key, $otherIds);
            })
        ); 
    }

    public function duplicates(string $key)
    {

    }

    /**
     * Walks through all items in
     * the Collection via a Closure
     *
     * @param \Closure $closure function to walk the Collection
     * @return bool
     */
    public function each(\Closure $closure)
    {
        return array_walk($this->items, $closure);
    }

    /**
     * Checks if all items in the
     * Collection passes through the
     * filter function passed as parameter
     *
     * @param \Closure $filter
     * @return boolean
     */
    public function every(\Closure $filter)
    {
        if($this->empty()) {
            return true;
        }

        return $this->filter($filter)->count() === $this->count();
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
     * Returns the collection with the
     * models without the attributes
     * passed as attributes
     *
     * @param ...$args attributes
     * @return Collection
     */
    public function except(...$args)
    {
        return new Collection(
            $this->class,
            array_filter($this->items, function($item) use ($args) {
                return $item->exceptAttributes(...$args);
            })
        );
    }

    /**
     * Returns a filtered collection with the
     * items that passed on the filter
     * function
     *
     * @param \Closure $filter filter function to apply
     * @return Collection
     */
    public function filter(\Closure $filter)
    {
        return new Collection(
            $this->class,
            array_filter($this->items, $filter, ARRAY_FILTER_USE_BOTH)
        );
    }

    /**
     * Returns a model based on its id
     *
     * @param integer $id Model's id
     * @return Model
     */
    public function find(int $id)
    {
        return array_filter($this->items, function($item) use ($id) {
            return $id === $item->id;
        })[0];
    }

    /**
     * Returns the first item of
     * the Collection
     *
     * @return Model
     */
    public function first()
    {
        return Arr::first($this->items);
    }

    /**
     * Returns the first Model in
     * which occurs the the equivalence
     * for the key value pair passed
     * as parameter
     *
     * @param string $key attribute name
     * @param mixed $value attribute value
     * @return Model
     */
    public function firstWhere(string $key, $value)
    {
        foreach($this->items as $item) {
            if($item->hasAttribute($key) && $item->$key === $value) {
                return $item;
            }
        }
    }

    /**
     * Returns the Model at the index
     * passed as attribute
     * 
     * @param int $index index to get
     * @return Model
     */
    public function get(int $index)
    {
        return $this->items[$index];
    }

    /**
     * Checks if all items have the
     * given attribute
     *
     * @param string $key attribute name
     * @return boolean
     */
    public function has(string $key)
    {
        foreach ($this->items as $item) {
            if (!$item->hasAttribute($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets all the attributes from the collection
     * with the attribute passed as parameter
     * and joins it with the glue
     *
     * @param string $key attribute to select
     * @param string $glue glue for the join
     * @return string
     */
    public function join(string $key, string $glue)
    {
        return join($glue, array_map(function($item) use ($key) {
            return $item->$key;
        }, $this->items));
    }

    /**
     * Gets the attributes names from 
     * the Model in the collection;
     * 
     * @return array
     */
    public function keys()
    {
        return array_keys($this->first()->getAttributes());
    }

    /**
     * Returns an array of which
     * the keys are the attributes
     * and it value is an array of
     * values from that attribute
     *
     * @return array
     */
    public function keysBy()
    {
        $keys = $this->keys();

        $result = [];
        foreach($keys as $key) {
            $result[$key] = $this->pluck($key);
        }

        return $result;
    }

    /**
     * Returns the last item of
     * the Collection
     *
     * @return Model
     */
    public function last()
    {
        return Arr::last($this->items);
    }

    /**
     * Retuns an array that passed
     * through an array map
     *
     * @param \Closure $closure map function to apply
     * @return array
     */
    public function map(\Closure $closure)
    {
        return array_map($closure, $this->items);
    }

    /**
     * Returns the maximum value of
     * a certain attribute from the
     * Collection
     *
     * @param string $key attribute to get max value
     * @return mixed
     */
    public function max(string $key)
    {
        return max($this->pluck($key));
    }

    /**
     * Returns the current collection
     * merged with the collection passed
     * as parameter
     * 
     * @return Collection
     */
    public function merge(Collection $collection)
    {
        if($collection->class() !== $this->class) {
            throw new \Exception("Impossible to merge two Collection of different Model classes");
        }

        $this->items = Arr::merge($this->items, $collection->all());
        return $this;
    }

    /**
     * Returns the minimum value of
     * a certain attribute from the
     * Collection
     *
     * @param string $key attribute to get min value
     * @return mixed
     */
    public function min(string $key)
    {
        return min($this->pluck($key));
    }

    /**
     * Returns the collection with the
     * models with just the attributes
     * passed as attributes
     *
     * @param ...$args attributes
     * @return Collection
     */
    public function only(...$args)
    {
        return new Collection(
            $this->class,
            array_filter($this->items, function ($item) use ($args) {
                return $item->onlyAttributes(...$args);
            })
        );
    }

    /**
     * Divides the collection into two
     * based on the conditions passed
     * as closure
     *
     * @param \Closure $closure
     * @return Collection[]
     */
    public function partition(\Closure $closure)
    {
        $filter = $this->filter($closure);
        return [
            new Collection($this->class, $filter->all()), 
            new Collection($this->class, $this->diff($filter)->all()) 
        ];
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
        return array_map(function ($item) use ($column) {
            return $item->$column;
        }, $this->items);
    }

    /**
     * Pops the last element of the
     * items out of the collection and
     * returns it
     * 
     * @return Model
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Prepends an item to the 
     * Collection
     *
     * @param Model $item item to prepend
     * @return Collection
     */
    public function prepend(Model $item)
    {
        if(get_class($item) !== $this->class) {
            throw new \Exception("Cannot prepend an item that is not from the same class as the one from the Collection");
        }

        Arr::prepend($this->items, $item);
        return $this;
    }

    /**
     * Pushes an item to the Collection
     *
     * @param Model $item item to push
     * @return Collection
     */
    public function push(Model $item)
    {
        if (get_class($item) !== $this->class) {
            throw new \Exception("Cannot push an item that is not from the same class as the one from the Collection");
        }

        array_push($this->items, $item);
        return $this;
    }

    /**
     * Returns a number of random
     * items from the Collection
     * 
     * @param int $size number of items to return
     * @return Model|Collection
     */
    public function random(int $size = 1)
    {
        if($size === 1) {
            return array_rand($this->items);
        }

        return new Collection(
            $this->class,
            array_rand($this->items, $size)
        );
    }

    /**
     * Reduces the Collection into a value
     *
     * @param \Closure $closure reduce function
     * @return mixed
     */
    public function reduce(\Closure $closure)
    {
        return array_reduce($this->items, $closure);
    }

    /**
     * Rejects the items that don't pass
     * in the truth test
     *
     * @param \Closure $closure
     * @return Collection
     */
    public function reject(\Closure $closure)
    {
        return $this->diff($this->filter($closure));
    }

    /**
     * Reverses the order of the items
     *
     * @return Collection
     */
    public function reverse()
    {
        $this->items = array_reverse($this->items);
        return $this;
    }

    /**
     * Returns the index of the item
     * that matches the key value pair
     * passed as parameter
     *
     * @param string $key attribute name
     * @param mixed $value attribute value
     * @return int|bool
     */
    public function search(string $key, $value)
    {
        foreach($this->items as $index => $item) {
            if($item->$key === $value) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Returns the first item in the collection
     * and shifts the remaining into position
     *
     * @return Model
     */
    public function shift()
    {
        $first = $this->first();
        unset($this->items[0]);
        $this->items = array_values($this->items);
        return $first;
    }

    /**
     * Shuffles the order of the Collection
     * 
     * @return Collection
     */
    public function shuffle()
    {
        Arr::shuffle($this->items);
        return $this;
    }

    /**
     * Gets a collction of the items
     * with the offset passed as
     * parameter
     * 
     * @param int $skip how many items to skip
     * @return Collection
     */
    public function skip(int $skip)
    {
        return new Collection(
            $this->class,
            array_slice($this->items, $skip)
        );
    }

    /**
     * Returns a new Collection with
     * a slice of the original one
     * 
     * @param int $offset slice index offset
     * @param int $length slice length
     * @return Collection
     */
    public function slice(int $offset, int $length = null)
    {
        $items = $length ? array_slice($this->items, $offset, $length) : array_slice($this->items, $offset);
        return new Collection(
            $this->class,
            $items
        );
    }

    /**
     * Returns a sorted Collection
     * that is sorted by a 
     * certain attribute
     * 
     * @param string $key attribute name
     * @param bool $desc sorts in descending order
     * @return Collection
     */
    public function sort(string $key, bool $desc = false)
    {
        $values = $this->pluck($key);
        
        if($desc) {
            arsort($values);
        } else {
            asort($values);
        }

        $new = new Collection($this->class);
        foreach(array_keys($values) as $index) {
            $new->push($this->get($index));
        }

        return $new;
    }

    public function split(int $size)
    {

    }

    /**
     * Returns the sum of the
     * values of a certain attribute
     * of the Models in the Collection
     * 
     * @param string $key attribute name
     * @return int|float
     */
    public function sum(string $key)
    {
        return array_sum($this->pluck($key));
    }

    /**
     * Takes the first items
     * from the collection until
     * a certain length
     * 
     * @param int $length chunk length
     * @return Collection
     */
    public function take($length)
    {
        return $this->slice(0, $length);
    }

    /**
     * Returns a JSON string of
     * the items in the Collection
     * 
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->items);
    }

    /**
     * Returns an array of values of
     * the attributes of the items in
     * the collection
     *
     * @return array
     */
    public function values()
    {
        return array_map(function($item) {
            return $item->getAttributes();
        }, $this->items);
    }

    /**
     * Performs a truth test based on the
     * comparison and the truth operator
     * 
     * @param string $key
     * @param string $operator
     * @param $value
     * @return \Closure
     */
    protected function evalOperator(string $key, string $operator = null, $value = null)
    {
        return function($item) use ($key, $operator, $value) {
            $retrieved = $item->$key;

            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
            }
        };
    }

    /**
     * Performs a where operation in
     * the Collection
     * 
     * @param ...$args
     * @return Collection
     */
    public function where(...$args)
    {
        $operator = "=";
        $value = true;

        if(count($args) === 1) {
            list($key) = $args;
        } else if(count($args) === 2) {
            list($key, $value) = $args;
        } else if(count($args) === 3) {
            list($key, $operator, $value) = $args;
        } else {
            throw new \Exception("Cannot use more than 3 arguments in this where function");
        }

        return $this->filter($this->evalOperator($key, $operator, $value));
    }

    /**
     * Returns the values that are between the 
     * first and last value of the array
     * 
     * @param string $key
     * @param array $value
     * @return Collection
     */
    public function whereBetween(string $key, array $value)
    {
        return $this->where($key, '>=', reset($value))->where($key, '<=', end($value));
    }

    /**
     * Returns the values that are not between the 
     * first and last value of the array
     * 
     * @param string $key
     * @param array $value
     * @return Collection
     */
    public function whereNotBetween(string $key, array $value)
    {
        return $this->filter(function($item) use ($key, $value) {
            return $item->$key < reset($value) || $item->$key > end($value);
        });
    }

    /**
     * Returns the values that are in
     * the array passed as parameter
     * 
     * @param string $key
     * @param array $value
     * @return Collection
     */
    public function whereIn(string $key, array $value)
    {
        return $this->filter(function ($item) use ($key, $value) {
            return in_array($item->$key, $value);
        });
    }

    /**
     * Returns the values that are not in
     * the array passed as parameter
     * 
     * @param string $key
     * @param array $value
     * @return Collection
     */
    public function whereNotIn(string $key, array $value)
    {
        return $this->filter(function ($item) use ($key, $value) {
            return !in_array($item->$key, $value);
        });
    }

    /**
     * Returns the items that have the
     * value as null
     * 
     * @param string $key
     * @param array $value
     * @return Collection
     */
    public function whereNull(string $key)
    {
        return $this->where($key, '===', null);
    }

    /**
     * Returns the items that don't have the
     * value as null
     * 
     * @param string $key
     * @param array $value
     * @return Collection
     */
    public function whereNotNull(string $key)
    {
        return $this->where($key, '!==', null);
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
