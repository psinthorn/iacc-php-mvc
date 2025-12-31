<?php

namespace App\Repositories;

use App\Foundation\Database;
use App\Foundation\QueryBuilder;
use App\Models\Model;

/**
 * RepositoryInterface - Contract for all repositories
 */
interface RepositoryInterface
{
    /**
     * Get all records
     * @return array
     */
    public function all();

    /**
     * Find record by ID
     * @param mixed $id
     * @return Model|null
     */
    public function find($id);

    /**
     * Find by column
     * @param string $column
     * @param mixed $value
     * @return Model|null
     */
    public function findBy($column, $value);

    /**
     * Get all matching where condition
     * @param string $column
     * @param mixed $value
     * @return array
     */
    public function where($column, $value);

    /**
     * Create new record
     * @param array $attributes
     * @return Model
     */
    public function create($attributes);

    /**
     * Update record
     * @param mixed $id
     * @param array $attributes
     * @return bool
     */
    public function update($id, $attributes);

    /**
     * Delete record
     * @param mixed $id
     * @return bool
     */
    public function delete($id);

    /**
     * Paginate results
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function paginate($page = 1, $perPage = 15);

    /**
     * Count records
     * @return int
     */
    public function count();
}

/**
 * Repository - Base repository class for models
 * 
 * Implements common data access patterns:
 * - CRUD operations
 * - Query building
 * - Pagination
 * - Filtering
 */
abstract class Repository implements RepositoryInterface
{
    /**
     * Database instance
     * @var Database
     */
    protected $database;

    /**
     * Model class
     * @var Model
     */
    protected $model;

    /**
     * Constructor
     * 
     * @param Database $database Database instance
     * @param Model $model Model instance
     */
    public function __construct(Database $database, Model $model)
    {
        $this->database = $database;
        $this->model = $model;
    }

    /**
     * Create a new query builder for the model's table
     * 
     * @return QueryBuilder
     */
    protected function query()
    {
        return new QueryBuilder($this->database, $this->model->getTable());
    }

    /**
     * Get all records
     * 
     * @return array
     */
    public function all()
    {
        $results = $this->query()->get();
        return array_map(function ($item) {
            return $this->hydrate($item);
        }, $results);
    }

    /**
     * Find record by primary key
     * 
     * @param mixed $id Primary key value
     * @return Model|null
     */
    public function find($id)
    {
        $result = $this->query()
            ->where($this->model->getKeyName(), '=', $id)
            ->first();

        return $result ? $this->hydrate($result) : null;
    }

    /**
     * Find by specific column
     * 
     * @param string $column Column name
     * @param mixed $value Value to search
     * @return Model|null
     */
    public function findBy($column, $value)
    {
        $result = $this->query()
            ->where($column, '=', $value)
            ->first();

        return $result ? $this->hydrate($result) : null;
    }

    /**
     * Find all where column matches value
     * 
     * @param string $column Column name
     * @param mixed $value Value to search
     * @return array
     */
    public function where($column, $value)
    {
        $results = $this->query()
            ->where($column, '=', $value)
            ->get();

        return array_map(function ($item) {
            return $this->hydrate($item);
        }, $results);
    }

    /**
     * Create a new model instance and save it
     * 
     * @param array $attributes Model attributes
     * @return Model
     */
    public function create($attributes)
    {
        $model = clone $this->model;
        $model->fill($attributes);

        // Insert into database
        $sql = 'INSERT INTO ' . $model->getTable() . ' (' . implode(', ', array_keys($attributes)) . ')
                VALUES (' . implode(', ', array_fill(0, count($attributes), '?')) . ')';

        $id = $this->database->insert($sql, array_values($attributes));

        // Set the ID on the model
        $model->setAttribute($model->getKeyName(), $id);

        return $model;
    }

    /**
     * Update an existing record
     * 
     * @param mixed $id Primary key value
     * @param array $attributes Attributes to update
     * @return bool
     */
    public function update($id, $attributes)
    {
        $sets = [];
        $values = [];

        foreach ($attributes as $key => $value) {
            $sets[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $sql = 'UPDATE ' . $this->model->getTable() . ' SET ' . implode(', ', $sets) . ' WHERE ' . $this->model->getKeyName() . ' = ?';

        return $this->database->update($sql, $values) > 0;
    }

    /**
     * Delete a record
     * 
     * @param mixed $id Primary key value
     * @return bool
     */
    public function delete($id)
    {
        $sql = 'DELETE FROM ' . $this->model->getTable() . ' WHERE ' . $this->model->getKeyName() . ' = ?';

        return $this->database->delete($sql, [$id]) > 0;
    }

    /**
     * Paginate results
     * 
     * @param int $page Page number (1-indexed)
     * @param int $perPage Items per page
     * @return array
     */
    public function paginate($page = 1, $perPage = 15)
    {
        $page = max(1, $page);
        $total = $this->count();

        $results = $this->query()
            ->paginate($page, $perPage)
            ->get();

        $models = array_map(function ($item) {
            return $this->hydrate($item);
        }, $results);

        return [
            'data' => $models,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ];
    }

    /**
     * Get count of records
     * 
     * @return int
     */
    public function count()
    {
        return $this->query()->count();
    }

    /**
     * Hydrate array into model instance
     * 
     * @param array $data Row data
     * @return Model
     */
    protected function hydrate($data)
    {
        $model = clone $this->model;
        $model->fill($data);
        return $model;
    }

    /**
     * Get the model instance
     * 
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the database instance
     * 
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }
}
