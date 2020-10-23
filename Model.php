<?php

namespace Pantheion\Model;

use JsonSerializable;
use Pantheion\Facade\Arr;
use Pantheion\Facade\Inflection;
use Pantheion\Model\Relation\BelongsTo;
use Pantheion\Model\Relation\BelongsToMany;
use Pantheion\Model\Relation\HasMany;
use Pantheion\Model\Relation\HasManyThrough;
use Pantheion\Model\Relation\HasOneThrough;

abstract class Model implements JsonSerializable
{
    /**
     * Array of key-value pairs
     * correspondent to the Model's
     * attributes
     *
     * @var array
     */
    protected $attributes;

    /**
     * Array of the initial key-value 
     * pairs correspondent to the Model's
     * attributes
     *
     * @var array
     */
    protected $initial;

    /**
     * Array of attributes that were
     * changed on an update
     *
     * @var array
     */
    protected $changed;

    /**
     * Model's table
     *
     * @var string $table
     */
    protected $table;

    /**
     * Model's constructor function.
     * It sets the Model's attributes
     *
     * @param array $attributes key-value array with the attributes names and their values
     */
    public function __construct(array $attributes = null)
    {
        $this->changed = $this->initial = $this->attributes = [];

        if($attributes) {
            $this->fill($attributes);
        }
    }

    /**
     * Fills the attributes array with the
     * array of attributes provided
     *
     * @param array $attributes
     * @return void
     */
    public function fill(array $attributes)
    {
        if(!Arr::isAssoc($attributes)) {
            throw new \Exception("Please provide an associative array with the attributes names as keys and their respective values");
        }

        foreach($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    /**
     * Returns a Collection with all the
     * Models from the Model's table
     *
     * @param array $columns columns to select
     * @return Collection
     */
    public static function all(array $columns = ['*'])
    {
        return static::query()->select(...$columns)->get();
    }

    /**
     * Returns a QueryBuilder with a prepared
     * where clause
     *
     * @param ...$args where clause conditions
     * @return QueryBuilder
     */
    public static function where(...$args)
    {
        return static::query()->where(...$args);
    }

    /**
     * Returns a fresh copy 
     * of the model
     *
     * @return Model
     */
    public function fresh()
    {
        if(!$this->hasId()) {
            throw new \Exception("Impossible to retrieve a fresh copy of the object since it has no 'id' attribute");
        }

        return static::query()->where('id', $this->id)->first();
    }

    /**
     * Refreshes the current model
     * into it's database state
     *
     * @return Model
     */
    public function refresh()
    {
        if (!$this->hasId()) {
            throw new \Exception("Impossible to retrieve a fresh copy of the object since it has no 'id' attribute");
        }

        /**
         * @var static $copy
         */
        $copy = static::query()->where('id', $this->id)->first();
        $this->attributes = $copy->attributes;

        return $this;
    }

    /**
     * Returns a Model instance 
     * with the id passed as argument
     *
     * @param int|int[] $id
     * @return Model
     */
    public static function find($id)
    {
        if(is_array($id)) {
            return static::query()->whereIn('id', $id)->get();
        }

        return static::query()->where('id', $id)->first();
    }

    /**
     * Either returns a Model instance 
     * with the id passed as argument
     * or throws an Exception
     *
     * @param int|int[] $id
     * @return Model
     */
    public static function findOrFail($id) 
    {
        $result = static::find($id);

        if(is_null($result) || ($result instanceof Collection && $result->empty())) {
            $notFoundIds = is_array($id) ? implode(", ", $id) : $id;
            throw new \Exception("Model(s) with the Id(s) {$notFoundIds} not found");
        }

        return $result;
    }

    /**
     * Insert or Mass Insert a number
     * of Models with the attributes
     * passed as parameter
     *
     * @param array $attributes
     * @return void
     */
    public static function create(array $attributes)
    {
        return static::query()->insert($attributes);
    }

    /**
     * Deletes Models from the table
     * with the id(s) passed as parameters
     *
     * @param ...$args ids to remove
     * @return void
     */
    public static function destroy(...$args)
    {
        if(count($args) > 1) {
            return static::query()->whereIn('id', $args)->delete();
        }

        if(count($args) === 1 && is_array($args[0])) {            
            return static::query()->whereIn('id', $args[0])->delete();
        }

        return static::query()->where('id', $args[0])->delete();
    }

    /**
     * Inserts or updates the Model
     * based on whether it has an 'id'
     * attribute or not
     *
     * @return int|null
     */
    public function save()
    {
        if($this->hasId()) {
            return static::query()->where('id', $this->id)->update(
                $this->changed = $this->getAttributesDiff()
            );
        }

        $attributes = static::query()->insert($this->attributes);
        $this->setAttributes($attributes, true);

        return $this->id;
    }

    /**
     * Deletes the current Model
     *
     * @return void
     */
    public function delete()
    {
        return static::query()->where('id', $this->id)->delete();
    }

    /**
     * Checks if the model is the
     * same as the one passed as 
     * attribute
     *
     * @param Model $other
     * @return boolean
     */
    public function is(Model $other)
    {
        return $this->table() === $other->table() && $this->id === $other->id;
    }

    /**
     * Adds a Belongs To relation
     *
     * @param string $belongsTo relation class
     * @return BelongsTo
     */
    public function belongsTo(string $belongsTo)
    {
        return new BelongsTo($this, $belongsTo);
    }

    /**
     * Adds a Has Many relation
     *
     * @param string $hasMany relation class
     * @return HasMany
     */
    public function hasMany(string $hasMany)
    {
        return new HasMany($this, $hasMany);
    }

    /**
     * Adds a Belongs To Many relation
     *
     * @param string $belongsToMany relation class
     * @param string $pivotTable pivot table
     * @return BelongsToMany
     */
    public function belongsToMany(string $belongsToMany, string $pivotTable = null)
    {
        return new BelongsToMany($this, $belongsToMany, $pivotTable);
    }

    /**
     * Adds a HasOneThrough relation
     *
     * @param string $hasOneThrough relation class
     * @param string $through through this class
     * @return HasOneThrough
     */
    public function hasOneThrough(string $hasOneThrough, string $through)
    {
        return new HasOneThrough($this, $hasOneThrough, $through);
    }

    /**
     * Adds a HasManyThrough relation
     *
     * @param string $hasManyThrough relation class
     * @param string $through through this class
     * @return HasManyThrough
     */
    public function hasManyThrough(string $hasManyThrough, string $through)
    {
        return new HasManyThrough($this, $hasManyThrough, $through);
    }

    /**
     * Returns an array with the
     * attributes that were changed
     *
     * @return array
     */
    protected function getAttributesDiff()
    {
        return array_diff_assoc($this->attributes, $this->initial);
    }

    /**
     * Checks if any of the attributes
     * was changed via an update
     *
     * @param string $attribute
     * @return bool
     */
    public function wasChanged(string $attribute = null)
    {
        if($attribute) {
            return array_key_exists($attribute, $this->changed);
        }

        return count($this->changed) > 0;
    } 

    /**
     * Checks if the Model had its
     * attributes changed
     *
     * @param string $attribute
     * @return boolean
     */
    public function isDirty(string $attribute = null)
    {
        if($attribute) {
            return array_key_exists(
                $attribute,
                $this->getAttributesDiff()
            );
        }

        return boolval(count($this->getAttributesDiff()));
    }

    /**
     * Checks if the Model has its attributes
     * intact since it was queried;
     *
     * @param string $attribute
     * @return boolean
     */
    public function isClean(string $attribute = null)
    {
        return $this->isDirty($attribute);
    }

    /**
     * Sets the attributes and checks if they
     * should be put as initial
     *
     * @param array $attributes
     * @param boolean $asInitial
     * @return Model
     */
    public function setAttributes(array $attributes, bool $asInitial = false) 
    {
        $this->attributes = $attributes;
        if($asInitial) {
            $this->initial = $this->attributes;
        }

        return $this;
    }

    /**
     * Returns the initial attributes or
     * a specific one of them if specified
     * in the parameter
     *
     * @param string $attribute
     * @return array|mixed
     */
    public function getInitial(string $attribute = null)
    {
        if($attribute) {
            if(!array_key_exists($attribute, $this->initial)) {
                throw new \Exception("The attribute {$attribute} doesn't exist in the initial attributes");
            }

            return $this->initial[$attribute];
        }

        return $this->initial;
    }

    /**
     * Returns the table correspondent
     * to the Model
     *
     * @return string
     */
    public function table()
    {
        return $this->table ?: $this->getTableFromModel();
    }

    /**
     * Uses inflection to formulate
     * the table's name based on the
     * Model class name
     *
     * @return string
     */
    protected function getTableFromModel()
    {
        return $this->table = Inflection::tablerize(
            (new \ReflectionClass($this))->getShortName()
        );
    }

    /**
     * Returns a QueryBuilder for the
     * Model's table
     *
     * @return QueryBuilder
     */
    protected static function query()
    {
        return (new static)->newQueryBuilder();
    }

    /**
     * Returns a new instance of a
     * QueryBuilder for this Model's table
     *
     * @return QueryBuilder
     */
    protected function newQueryBuilder()
    {
        return new QueryBuilder($this->table(), static::class);
    }

    /**
     * Checks if the current Model
     * has an 'id' attribute
     *
     * @return boolean
     */
    protected function hasId()
    {
        return array_key_exists('id', $this->attributes);
    }

    /**
     * Returns the Model current
     * attributes in an array
     * 
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Checks if the Model has the
     * attribute with the name passed
     * as parameter
     *
     * @param string $key attribute name
     * @return boolean
     */
    public function hasAttribute(string $key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Returns the Model without
     * the attributes passed as parameters
     *
     * @param ...$args
     * @return Model
     */
    public function exceptAttributes(...$args)
    {
        foreach($args as $attribute)
        {
            unset($this->attributes[$attribute]);
        }

        return $this;
    }

    /**
     * Returns the Model with just
     * the attributes passed as parameters
     *
     * @param ...$args
     * @return Model
     */
    public function onlyAttributes(...$args)
    {
        foreach($this->attributes as $attribute => $value) {
            if(!in_array($attribute, $args)) {
                unset($this->attributes[$attribute]);
            }
        }

        return $this;
    }

    /**
     * PHP's magic method __get
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if(method_exists($this, $name)) {
            if(array_key_exists($name, $this->attributes)) {
                return $this->attributes[$name];
            }

            return $this->attributes[$name] = $this->$name()->get();
        }

        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : null;
    }

    /**
     * PHP's magic method __set
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Serializes the Model into a JSON
     * string
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->serializeAttributes();
    }

    /**
     * Prepares the attributes for conversion
     * into JSON
     *
     * @return array
     */
    protected function serializeAttributes()
    {
        $attributes = [];
        foreach($this->attributes as $key => $value) {
            $attributes[$key] = $value instanceof \DateTime ? $value->format("Y-m-d H:i:s") : $value;
        }

        return $attributes;
    }
}