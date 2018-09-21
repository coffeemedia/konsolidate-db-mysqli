<?php

declare(strict_types=1);

/**
 * MySQLi specific Exception class.
 * @var class
 * @author  John Beitler <john@coffeemedia.nl>
 */
class KContribDB_DBMySQLiException extends Exception
{
    /**
     * The error message.
     * @var mysqli_error
     */
    public $error;

    /**
     * The error number.
     * @var mysqli_errno
     */
    public $errno;

    /**
     * Constructor.
     * @note This object is constructed by KContribDB_DBMySQLi as 'status report'
     */
    public function __construct(MySQLi &$connection)
    {
        $this->error = is_object($connection) ? mysqli_error($connection) : mysqli_error();
        $this->errno = is_object($connection) ? mysqli_errno($connection) : mysqli_errno();
    }
}
