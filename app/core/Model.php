<?php

namespace App\Core;

use PDO;

/**
 * Base model.
 *
 * Provides the handful of helpers every table needs - find, findAll, create,
 * update, delete, count and paginate - so concrete models stay focused on the
 * queries that are actually specific to them.
 *
 * Child classes set $table (and usually $fillable).
 */
abstract class Model
{
    /** @var string Table name */
    protected $table = '';

    /** @var array Columns that may be written by create()/update() */
    protected $fillable = [];

    /** @var string Default column ordering applied when none is given */
    protected $defaultOrder = 'id DESC';

    public static function tableName()
    {
        $model = new static();

        return $model->table;
    }

    /** The shared PDO connection. */
    protected function db()
    {
        return Database::connection();
    }

    /** Find one row by primary key, or null. */
    public function find($id)
    {
        return Database::fetch(
            'SELECT * FROM `' . $this->table . '` WHERE `id` = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * Find several rows.
     *
     * @param string $orderBy Raw ORDER BY fragment, e.g. 'created_at DESC'
     * @param int    $limit  0 for no limit
     * @param string $where  Raw WHERE clause without the keyword
     * @param array  $params Bindings for $where
     */
    public function findAll($orderBy = null, $limit = 0, $where = '', array $params = [])
    {
        $sql = 'SELECT * FROM `' . $this->table . '`';

        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }

        $sql .= ' ORDER BY ' . ($orderBy !== null ? $orderBy : $this->defaultOrder);

        if ($limit > 0) {
            // LIMIT/OFFSET must be bound as integers for the driver to accept them.
            $sql .= ' LIMIT ' . (int) $limit;
        }

        return Database::fetchAll($sql, $params);
    }

    /**
     * Insert a row from a set of values.
     *
     * @return int The new id
     */
    public function create(array $values)
    {
        $values = $this->filter($values);

        if (empty($values)) {
            throw new \InvalidArgumentException('Cannot insert an empty row into ' . $this->table . '.');
        }

        $columns = array_keys($values);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = 'INSERT INTO `' . $this->table . '` (`' . implode('`, `', $columns) . '`) VALUES ('
            . implode(', ', $placeholders) . ')';

        return Database::insert($sql, array_values($values));
    }

    /**
     * Update a row by primary key.
     *
     * @return int Affected rows
     */
    public function update($id, array $values)
    {
        $values = $this->filter($values);

        if (empty($values)) {
            return 0;
        }

        $assignments = [];

        foreach (array_keys($values) as $column) {
            $assignments[] = '`' . $column . '` = ?';
        }

        $sql = 'UPDATE `' . $this->table . '` SET ' . implode(', ', $assignments) . ' WHERE `id` = ?';
        $bindings = array_values($values);
        $bindings[] = $id;

        return Database::execute($sql, $bindings);
    }

    /** Delete a row by primary key. */
    public function delete($id)
    {
        return Database::execute('DELETE FROM `' . $this->table . '` WHERE `id` = ?', [$id]);
    }

    /** Number of rows matching a condition. */
    public function count($where = '', array $params = [])
    {
        $sql = 'SELECT COUNT(*) FROM `' . $this->table . '`';

        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }

        return (int) Database::fetchColumn($sql, $params);
    }

    /** True when at least one row matches. */
    public function exists($where, array $params = [])
    {
        return $this->count($where, $params) > 0;
    }

    /**
     * Fetch one page of rows plus the total row count for the same condition.
     *
     * @return array{rows: array, total: int, page: int, per_page: int, last_page: int}
     */
    public function paginate($where, array $params, $page, $perPage, $orderBy = null)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);

        $total = $this->count($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));

        // Ask for a page that exists, so deleting rows cannot leave a blank view.
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT * FROM `' . $this->table . '`';

        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }

        $sql .= ' ORDER BY ' . ($orderBy !== null ? $orderBy : $this->defaultOrder)
            . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

        return [
            'rows' => Database::fetchAll($sql, $params),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    /**
     * Keep only fillable columns, so a request array can never write a column
     * the model did not intend to expose (password hashes, ids, roles).
     */
    protected function filter(array $values)
    {
        if (empty($this->fillable)) {
            return $values;
        }

        return array_intersect_key($values, array_flip($this->fillable));
    }
}
