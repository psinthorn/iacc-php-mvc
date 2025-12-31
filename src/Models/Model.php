<?php

namespace App\Models;

use DateTime;
use JsonSerializable;

/**
 * Model - Base class for all database models
 * 
 * Provides common functionality for models:
 * - Attribute management and casting
 * - Timestamps (created_at, updated_at)
 * - Relationships (hasMany, belongsTo, etc.)
 * - Validation hooks
 * - Array/JSON conversion
 */
abstract class Model implements JsonSerializable
{
    /**
     * Database table name
     * @var string
     */
    protected $table;

    /**
     * Primary key column name
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Attributes that can be mass assigned
     * @var array
     */
    protected $fillable = [];

    /**
     * Model attributes
     * @var array
     */
    protected $attributes = [];

    /**
     * Original attribute values (for dirty detection)
     * @var array
     */
    protected $original = [];

    /**
     * Loaded relationships
     * @var array
     */
    protected $relations = [];

    /**
     * Cast attribute types
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Dates that should be cast to Carbon instances
     * @var array
     */
    protected $dates = [];

    /**
     * Hidden attributes (not exposed in toArray/JSON)
     * @var array
     */
    protected $hidden = [];

    /**
     * Timestamps enabled/disabled
     * @var bool
     */
    public $timestamps = true;

    /**
     * Constructor
     * 
     * @param array $attributes Initial attributes
     */
    public function __construct($attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    /**
     * Fill the model with an array of attributes
     * 
     * @param array $attributes
     * @return self
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable) || $this->fillable === ['*']) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Set an attribute value
     * 
     * @param string $key Attribute name
     * @param mixed $value Value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get an attribute value
     * 
     * @param string $key Attribute name
     * @param mixed $default Default value
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        if (!isset($this->attributes[$key])) {
            return $default;
        }

        $value = $this->attributes[$key];

        // Cast value if needed
        if (isset($this->casts[$key])) {
            return $this->castValue($this->casts[$key], $value);
        }

        return $value;
    }

    /**
     * Cast attribute to proper type
     * 
     * @param string $type Cast type
     * @param mixed $value Value to cast
     * @return mixed
     */
    protected function castValue($type, $value)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
            case 'double':
                return (float) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'string':
                return (string) $value;
            case 'datetime':
                return $value instanceof DateTime ? $value : new DateTime($value);
            case 'timestamp':
                return strtotime($value);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Magic method to access attributes
     * 
     * @param string $key Attribute name
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic method to set attributes
     * 
     * @param string $key Attribute name
     * @param mixed $value Value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Check if attribute exists
     * 
     * @param string $key Attribute name
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Check if model has been modified
     * 
     * @param string|null $key Specific attribute to check
     * @return bool
     */
    public function isDirty($key = null)
    {
        if ($key === null) {
            return $this->attributes !== $this->original;
        }

        return ($this->original[$key] ?? null) !== ($this->attributes[$key] ?? null);
    }

    /**
     * Get dirty attributes
     * 
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if ($value !== ($this->original[$key] ?? null)) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Define a relationship
     * 
     * @param string $name Relationship name
     * @param mixed $related Related model(s)
     * @return void
     */
    protected function setRelation($name, $related)
    {
        $this->relations[$name] = $related;
    }

    /**
     * Get a relationship
     * 
     * @param string $name Relationship name
     * @return mixed
     */
    public function getRelation($name)
    {
        return $this->relations[$name] ?? null;
    }

    /**
     * Define has-many relationship
     * 
     * @param string $related Related model class
     * @param string $foreignKey Foreign key column
     * @param string $localKey Local key column
     * @return \App\Models\Relations\HasMany
     */
    protected function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: strtolower(class_basename($this)) . '_id';
        $localKey = $localKey ?: $this->primaryKey;

        return new Relations\HasMany(
            new $related(),
            $this,
            $foreignKey,
            $localKey
        );
    }

    /**
     * Define belongs-to relationship
     * 
     * @param string $related Related model class
     * @param string $foreignKey Foreign key column
     * @param string $ownerKey Owner key column
     * @return \App\Models\Relations\BelongsTo
     */
    protected function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        if ($foreignKey === null) {
            $foreignKey = strtolower(class_basename($related)) . '_id';
        }
        $ownerKey = $ownerKey ?: 'id';

        return new Relations\BelongsTo(
            new $related(),
            $this,
            $foreignKey,
            $ownerKey
        );
    }

    /**
     * Convert model to array
     * 
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                // Cast value if needed
                if (isset($this->casts[$key])) {
                    $value = $this->castValue($this->casts[$key], $value);
                }
                $array[$key] = $value;
            }
        }
        return $array;
    }

    /**
     * Convert to JSON (for json_encode)
     * 
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert to JSON string
     * 
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Get all attributes
     * 
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get primary key value
     * 
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Check if model exists in database
     * 
     * @return bool
     */
    public function exists()
    {
        return $this->getKey() !== null;
    }

    /**
     * Get table name
     * 
     * @return string
     */
    public function getTable()
    {
        return $this->table ?: strtolower(class_basename($this)) . 's';
    }

    /**
     * Get primary key name
     * 
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Save the model (insert or update)
     * 
     * @return bool
     */
    public function save()
    {
        // Add timestamps if enabled
        if ($this->timestamps) {
            $now = (new DateTime())->format('Y-m-d H:i:s');
            if (!$this->exists()) {
                $this->setAttribute('created_at', $now);
            }
            $this->setAttribute('updated_at', $now);
        }

        // Call save hook
        if ($this->exists()) {
            $result = $this->update();
        } else {
            $result = $this->insert();
        }

        if ($result) {
            $this->original = $this->attributes;
        }

        return $result;
    }

    /**
     * Insert new record (to be implemented by child classes)
     * 
     * @return bool
     */
    protected function insert()
    {
        // Will be implemented in repository
        throw new \Exception('Insert not implemented in ' . class_basename($this));
    }

    /**
     * Update existing record (to be implemented by child classes)
     * 
     * @return bool
     */
    protected function update()
    {
        // Will be implemented in repository
        throw new \Exception('Update not implemented in ' . class_basename($this));
    }

    /**
     * Delete the model
     * 
     * @return bool
     */
    public function delete()
    {
        // Will be implemented in repository
        throw new \Exception('Delete not implemented in ' . class_basename($this));
    }

    /**
     * Validation rules
     * 
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Validate the model
     * 
     * @return bool
     */
    public function validate()
    {
        // Will be implemented with validation logic
        return true;
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function errors()
    {
        return [];
    }

    /**
     * Create a new model instance
     * 
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes)
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Create new empty instance
     * 
     * @return static
     */
    public static function make(array $attributes = [])
    {
        return new static($attributes);
    }

    /**
     * Debug representation
     * 
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}
