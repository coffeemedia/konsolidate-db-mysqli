<?php

declare(strict_types=1);

/**
 * MySQLi class
 * @var class
 * @author  John Beitler <john@coffeemedia.nl>
 */
class KContribDB_DBMySQLi extends KContribDB_DB
{
    /**
     * The error object (Exception which isn't thrown).
     * @var KContribDB_DBMySQLiException
     */
    public $error;

    /**
     * The connection URI (parsed url).
     * @var string[]
     */
    protected $uri;

    /**
     * The connection resource.
     * @var mysqli
     */
    protected $connection;

    /**
     * The query cache.
     * @var Query[]
     */
    protected $cache = [];

    /**
     * Wether or not a transaction is going on.
     * @var bool
     */
    protected $transaction = false;

    /**
     * Assign the connection DSN.
     * @return string[]
     */
    public function setConnectionUri(string $uri): array
    {
        assert(is_string($uri));

        $this->uri = parse_url($uri);

        return $this->uri;
    }

    /**
     * Connect to the database.
     * @note An explicit call to this method is not required, since the query method will create the connection if it isn't connected
     */
    public function connect(): bool
    {
        if (!$this->isConnected()) {
            $this->connection = new mysqli(
                sprintf('%s:%d', $this->uri['host'], isset($this->uri['port']) ? $this->uri['port'] : 3306),
                $this->uri['user'],
                $this->uri['pass']
            );

            if (false === $this->connection || !$this->connection->select_db(trim($this->uri['path'], '/'))) {
                $this->import('mysqli/exception.class.php');
                $this->error = new KContribDB_DBMySQLiException($this->connection);
                $this->connection = null;

                return false;
            }
        }

        return true;
    }

    /**
     * Check to see whether a connection is established.
     * @return bool success
     */
    public function isConnected(): bool
    {
        if (is_object($this->connection) && $this->connection instanceof mysqli) {
            return (bool) $this->connection->thread_id;
        }

        return false;
    }

    /**
     * Query the database.
     */
    public function query(string $query, bool $useCache = true): KContribDB_DBMySQLiQuery
    {
        $cacheKey = md5($query);

        if ($useCache && array_key_exists($cacheKey, $this->cache)) {
            $cache = $this->cache[$cacheKey];
            $cache->rewind();

            return $cache;
        }

        if ($this->connect()) {
            $queryObject = $this->instance('Query');
            $queryObject->execute($query, $this->connection);

            if ($useCache && $this->isCachableQuery($query)) {
                $this->cache[$cacheKey] = $queryObject;
            }

            return $queryObject;
        }

        return false;
    }

    /**
     * Get the ID of the last inserted record.
     * @return int last inserted id
     */
    public function lastInsertID(): int
    {
        if ($this->isConnected()) {
            return mysqli_insert_id($this->connection);
        }

        return -1;
    }

    /**
     * Get the ID of the last inserted record.
     * @var    method
     * @return int    last inserted id
     * @note   alias for lastInsertID
     * @see    lastInsertID
     */
    public function lastId(): int
    {
        return $this->lastInsertID();
    }

    /**
     * Properly escape a string.
     * @var method
     */
    public function escape(string $string): string
    {
        if ($this->connect()) {
            return mysqli_real_escape_string($this->connection, $string);
        }

        throw new Exception(sprintf('%s::escape, could not escape string \'%s\'', get_class($this), $string));
        exit(0);
    }

    /**
     * Quote and escape a string.
     * @var method
     */
    public function quote(string $value): string
    {
        return sprintf('\'%s\'', $this->escape($value));
    }

    /**
     * Start transaction.
     * @var method
     */
    public function startTransaction(): bool
    {
        if (!$this->transaction) {
            $result = $this->query('START TRANSACTION');
            if (is_object($result) && $result->errno <= 0) {
                $this->transaction = true;
            }
        }

        return $this->transaction;
    }

    /**
     * End transaction by sending 'COMMIT' or 'ROLLBACK'.
     * @var method
     * @note    if argument 'commit' is true, 'COMMIT' is sent, 'ROLLBACK' otherwise
     */
    public function endTransaction(bool $success = true): bool
    {
        if ($this->transaction) {
            $result = $this->query($success ? 'COMMIT' : 'ROLLBACK');
            if (is_object($result) && $result->errno <= 0) {
                $this->transaction = false;

                return true;
            }
        }

        return $this->transaction;
    }

    /**
     * Commit a transaction.
     * @var method
     * @note    same as endTransaction(true);
     */
    public function commitTransaction(): bool
    {
        return $this->endTransaction(true);
    }

    /**
     * Rollback a transaction.
     * @var method
     * @note    same as endTransaction(false);
     */
    public function rollbackTransaction(): bool
    {
        return $this->endTransaction(false);
    }

    /**
     * Determine whether a query should be cached (this applies only to 'SELECT' queries).
     * @var method
     */
    protected function isCachableQuery(string $query): bool
    {
        return (bool) preg_match('/^\s*SELECT /i', $query);
    }
}
