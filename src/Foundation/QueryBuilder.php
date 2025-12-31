<?php

namespace App\Foundation;

/**
 * QueryBuilder - Fluent SQL query builder
 * 
 * Build dynamic SQL queries with:
 * - WHERE clauses with operators
 * - SELECT, FROM, JOIN, GROUP BY, ORDER BY, LIMIT
 * - Parameter binding for security
 * - Chainable API
 */
class QueryBuilder
{
    /**
     * Database instance
     * @var Database
     */
    protected $database;

    /**
     * Table name
     * @var string
     */
    protected $table;

    /**
     * SELECT clause columns
     * @var array
     */
    protected $select = ['*'];

    /**
     * WHERE conditions
     * @var array
     */
    protected $where = [];

    /**
     * JOIN clauses
     * @var array
     */
    protected $joins = [];

    /**
     * GROUP BY columns
     * @var array
     */
    protected $groupBy = [];

    /**
     * HAVING conditions
     * @var array
     */
    protected $having = [];

    /**
     * ORDER BY columns
     * @var array
     */
    protected $orderBy = [];

    /**
     * LIMIT value
     * @var int
     */
    protected $limit;

    /**
     * OFFSET value
     * @var int
     */
    protected $offset;

    /**
     * Parameter bindings
     * @var array
     */
    protected $bindings = [];

    /**
     * Constructor
     * 
     * @param Database $database Database instance
     * @param string $table Table name
     */
    public function __construct(Database $database, $table)
    {
        $this->database = $database;
        $this->table = $table;
    }

    /**
     * Select specific columns
     * 
     * @param array|string $columns Column names
     * @return self
     */
    public function select($columns = ['*'])
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->select = $columns;
        return $this;
    }

    /**
     * Add WHERE condition
     * 
     * @param string $column Column name
     * @param string $operator Operator (=, !=, <, >, <=, >=, LIKE, IN, BETWEEN)
     * @param mixed $value Value to compare
     * @param string $logic AND or OR
     * @return self
     */
    public function where($column, $operator = '=', $value = null, $logic = 'AND')
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'logic' => $logic,
        ];

        // Add binding
        if (!in_array($operator, ['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'])) {
            $this->bindings[] = $value;
        } elseif (is_array($value)) {
            $this->bindings = array_merge($this->bindings, $value);
        }

        return $this;
    }

    /**
     * Add OR WHERE condition
     * 
     * @param string $column Column name
     * @param string $operator Operator
     * @param mixed $value Value
     * @return self
     */
    public function orWhere($column, $operator = '=', $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * WHERE IN condition
     * 
     * @param string $column Column name
     * @param array $values Values
     * @return self
     */
    public function whereIn($column, $values)
    {
        return $this->where($column, 'IN', $values);
    }

    /**
     * WHERE NOT IN condition
     * 
     * @param string $column Column name
     * @param array $values Values
     * @return self
     */
    public function whereNotIn($column, $values)
    {
        return $this->where($column, 'NOT IN', $values);
    }

    /**
     * WHERE BETWEEN condition
     * 
     * @param string $column Column name
     * @param mixed $start Start value
     * @param mixed $end End value
     * @return self
     */
    public function whereBetween($column, $start, $end)
    {
        return $this->where($column, 'BETWEEN', [$start, $end]);
    }

    /**
     * WHERE NULL condition
     * 
     * @param string $column Column name
     * @return self
     */
    public function whereNull($column)
    {
        $this->where[] = [
            'column' => $column,
            'operator' => 'IS NULL',
            'value' => null,
            'logic' => 'AND',
        ];
        return $this;
    }

    /**
     * WHERE NOT NULL condition
     * 
     * @param string $column Column name
     * @return self
     */
    public function whereNotNull($column)
    {
        $this->where[] = [
            'column' => $column,
            'operator' => 'IS NOT NULL',
            'value' => null,
            'logic' => 'AND',
        ];
        return $this;
    }

    /**
     * JOIN clause
     * 
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @param string $type JOIN type (INNER, LEFT, RIGHT)
     * @return self
     */
    public function join($table, $first, $operator = '=', $second = null, $type = 'INNER')
    {
        if ($second === null) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * LEFT JOIN clause
     * 
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @return self
     */
    public function leftJoin($table, $first, $operator = '=', $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * RIGHT JOIN clause
     * 
     * @param string $table Table to join
     * @param string $first First column
     * @param string $operator Operator
     * @param string $second Second column
     * @return self
     */
    public function rightJoin($table, $first, $operator = '=', $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * GROUP BY clause
     * 
     * @param array|string $columns Column names
     * @return self
     */
    public function groupBy($columns)
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * HAVING clause
     * 
     * @param string $column Column name
     * @param string $operator Operator
     * @param mixed $value Value
     * @return self
     */
    public function having($column, $operator = '=', $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * ORDER BY clause
     * 
     * @param string $column Column name
     * @param string $direction ASC or DESC
     * @return self
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];
        return $this;
    }

    /**
     * LIMIT clause
     * 
     * @param int $limit Number of rows
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * OFFSET clause
     * 
     * @param int $offset Number of rows to skip
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * PAGINATION helper
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return self
     */
    public function paginate($page = 1, $perPage = 15)
    {
        $page = max(1, (int) $page);
        $this->offset(($page - 1) * $perPage);
        $this->limit($perPage);
        return $this;
    }

    /**
     * Build the SQL query string
     * 
     * @return string
     */
    public function buildSql()
    {
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= ' FROM ' . $this->table;

        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= ' ' . $join['type'] . ' JOIN ' . $join['table'];
            $sql .= ' ON ' . $join['first'] . ' ' . $join['operator'] . ' ' . $join['second'];
        }

        // Add WHERE conditions
        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $conditions = [];
            foreach ($this->where as $i => $condition) {
                $logic = $i === 0 ? '' : ' ' . $condition['logic'] . ' ';
                $op = $condition['operator'];

                if (in_array($op, ['IS NULL', 'IS NOT NULL'])) {
                    $conditions[] = $logic . $condition['column'] . ' ' . $op;
                } elseif (in_array($op, ['IN', 'NOT IN'])) {
                    $placeholders = implode(', ', array_fill(0, count($condition['value']), '?'));
                    $conditions[] = $logic . $condition['column'] . ' ' . $op . ' (' . $placeholders . ')';
                } elseif (in_array($op, ['BETWEEN', 'NOT BETWEEN'])) {
                    $conditions[] = $logic . $condition['column'] . ' ' . $op . ' ? AND ?';
                } else {
                    $conditions[] = $logic . $condition['column'] . ' ' . $op . ' ?';
                }
            }
            $sql .= implode('', $conditions);
        }

        // Add GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // Add HAVING
        if (!empty($this->having)) {
            $sql .= ' HAVING ';
            $conditions = [];
            foreach ($this->having as $i => $condition) {
                $logic = $i === 0 ? '' : ' AND ';
                $conditions[] = $logic . $condition['column'] . ' ' . $condition['operator'] . ' ?';
                $this->bindings[] = $condition['value'];
            }
            $sql .= implode('', $conditions);
        }

        // Add ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ';
            $orders = [];
            foreach ($this->orderBy as $order) {
                $orders[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= implode(', ', $orders);
        }

        // Add LIMIT and OFFSET
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    /**
     * Execute query and get all results
     * 
     * @return array
     */
    public function get()
    {
        $sql = $this->buildSql();
        return $this->database->select($sql, $this->bindings);
    }

    /**
     * Execute query and get first result
     * 
     * @return array|null
     */
    public function first()
    {
        $this->limit(1);
        $sql = $this->buildSql();
        return $this->database->selectOne($sql, $this->bindings);
    }

    /**
     * Count rows
     * 
     * @return int
     */
    public function count()
    {
        $this->select(['COUNT(*) as count']);
        $result = $this->first();
        return $result ? (int) $result['count'] : 0;
    }

    /**
     * Check if query has results
     * 
     * @return bool
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Get maximum value
     * 
     * @param string $column Column name
     * @return mixed
     */
    public function max($column)
    {
        $this->select(["MAX($column) as max"]);
        $result = $this->first();
        return $result ? $result['max'] : null;
    }

    /**
     * Get minimum value
     * 
     * @param string $column Column name
     * @return mixed
     */
    public function min($column)
    {
        $this->select(["MIN($column) as min"]);
        $result = $this->first();
        return $result ? $result['min'] : null;
    }

    /**
     * Get sum of column
     * 
     * @param string $column Column name
     * @return mixed
     */
    public function sum($column)
    {
        $this->select(["SUM($column) as sum"]);
        $result = $this->first();
        return $result ? $result['sum'] : null;
    }

    /**
     * Get average of column
     * 
     * @param string $column Column name
     * @return mixed
     */
    public function avg($column)
    {
        $this->select(["AVG($column) as avg"]);
        $result = $this->first();
        return $result ? $result['avg'] : null;
    }

    /**
     * Get SQL string for debugging
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->buildSql();
    }
}
