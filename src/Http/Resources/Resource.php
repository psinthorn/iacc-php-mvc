<?php

namespace App\Http\Resources;

/**
 * Base Resource Class
 * Transforms models to API response format
 */
abstract class Resource
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Transform model to array
     */
    abstract public function toArray();

    /**
     * Get JSON representation
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Transform collection of models
     */
    public static function collection($models)
    {
        return array_map(function ($model) {
            return (new static($model))->toArray();
        }, $models);
    }

    /**
     * Get model attribute
     */
    protected function when($condition, $value, $default = null)
    {
        return $condition ? $value : $default;
    }

    /**
     * Merge additional data
     */
    protected function mergeWith($data)
    {
        return array_merge($this->toArray(), $data);
    }
}
