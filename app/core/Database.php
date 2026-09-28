<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Thin PDO wrapper: one shared connection, prepared statements everywhere and
 * small helpers so models never build SQL strings by hand.
 *
 * Every value that reaches the database goes through a bound parameter. The
 * connection runs with emulated prepares disabled, so the driver sends queries
 * and parameters separately to MySQL rather than interpolating client side.
 */
class Database
{
    /** @var PDO|null */
    private static $connection;

    /** Number of statements executed, handy while debugging. */
    private static $queryCount = 0;

    public static function connection()
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = App::config('database');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        try {
            self::$connection = new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (PDOException $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());
            throw new \RuntimeException(
                'Could not connect to the database. Check your credentials in config/config.php.',
                0,
                $exception
            );
        }

        // Fail loudly on bad data instead of silently storing truncated or empty
        // values, which is what an ENUM or length violation would otherwise do.
        self::$connection->exec(
            "SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'"
        );

        return self::$connection;
    }

    /**
     * Prepare, execute and return the statement.
     *
     * @param array $params Values bound with PDO::PARAM_STR
     */
    public static function query($sql, array $params = [])
    {
        $statement = self::connection()->prepare($sql);

        foreach ($params as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : $key;

            // Null and booleans must be bound as their own types or MySQL sees ''.
            if ($value === null) {
                $statement->bindValue($parameter, null, PDO::PARAM_NULL);
            } elseif (is_bool($value)) {
                $statement->bindValue($parameter, $value ? 1 : 0, PDO::PARAM_INT);
            } elseif (is_int($value)) {
                $statement->bindValue($parameter, $value, PDO::PARAM_INT);
            } else {
                $statement->bindValue($parameter, $value, PDO::PARAM_STR);
            }
        }

        $statement->execute();
        self::$queryCount++;

        return $statement;
    }

    /** Fetch a single row, or null. */
    public static function fetch($sql, array $params = [])
    {
        $row = self::query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** Fetch every matching row. */
    public static function fetchAll($sql, array $params = [])
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** Fetch the first column of the first row, e.g. for COUNT(*). */
    public static function fetchColumn($sql, array $params = [], $column = 0)
    {
        $value = self::query($sql, $params)->fetchColumn($column);

        return $value === false ? null : $value;
    }

    /**
     * Run an INSERT and return the new id.
     */
    public static function insert($sql, array $params = [])
    {
        self::query($sql, $params);

        return (int) self::connection()->lastInsertId();
    }

    /**
     * Run an UPDATE or DELETE and return the number of affected rows.
     */
    public static function execute($sql, array $params = [])
    {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Run a callback inside a transaction, rolling back if it throws.
     *
     * @param callable $callback
     *
     * @return mixed The callback's return value
     */
    public static function transaction($callback)
    {
        $connection = self::connection();
        $connection->beginTransaction();

        try {
            $result = call_user_func($callback);
            $connection->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function queryCount()
    {
        return self::$queryCount;
    }
}
