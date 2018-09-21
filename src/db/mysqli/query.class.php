<?php

declare(strict_types=1);

/**
 * MySQLi specific Query class.
 * @var class
 * @author  John Beitler <john@coffeemedia.nl>
 */
class KContribDB_DBMySQLiQuery extends Konsolidate
{
    /**
     * The exception object, used to populate 'error' and 'errno' properties.
     * @var object
     */
    public $exception;

    /**
     * The error message.
     * @var string
     */
    public $error;

    /**
     * The error number.
     * @var int
     */
    public $errno;

    /**
     * The connection resource.
     * @var object
     */
    protected $connection;

    /**
     * The result resource.
     * @var resource
     */
    protected $result;

    /**
     * Execute given query on given connection.
     */
    public function execute(string $query, mysqli &$connection)
    {
        $this->connection = $connection;
        $this->result = mysqli_query($this->connection, $query);

        if ($this->result instanceof mysqli_result) {
            $this->rows = mysqli_num_rows($this->result);
        } elseif (true === $this->result) {
            $this->rows = mysqli_affected_rows($this->connection);
        }

        //  We want the exception object to tell us everything is going extremely well, don't throw it!
        $this->import('../exception.class.php');
        $this->exception = new KContribDB_DBMySQLiException($this->connection);

        $this->errno = &$this->exception->errno;
        $this->error = &$this->exception->error;
    }

    /**
     * Rewind the internal resultset.
     * @return bool success
     */
    public function rewind(): bool
    {
        if ($this->result instanceof mysqli_result && mysqli_num_rows($this->result) > 0) {
            return mysqli_data_seek($this->result, 0);
        }

        return false;
    }

    /**
     * Get the next result from the internal resultset.
     * @return mixed resultrow, or false on failure
     */
    public function next()
    {
        if ($this->result instanceof mysqli_result) {
            return mysqli_fetch_object($this->result);
        }

        return false;
    }

    /**
     * Get the ID of the last inserted record.
     * @return int id
     */
    public function lastInsertID(): int
    {
        return mysqli_insert_id($this->connection);
    }

    /**
     * Get the ID of the last inserted record. Alias for lastInsertID.
     * @return int id
     * @see     lastInsertID
     */
    public function lastId(): int
    {
        return $this->lastInsertID();
    }

    /**
     * Retrieve an array containing all resultrows as objects.
     * @return object[] $return
     */
    public function fetchAll(): array
    {
        $return = [];
        while ($record = $this->next()) {
            array_push($return, $record);
        }
        $this->rewind();

        return $return;
    }
}
