<?php

declare(strict_types=1);

/**
 * DB class
 * @var class
 * @author  John Beitler <john@coffeemedia.nl>
 */
class KContribDB_DB extends Konsolidate
{
    /**
     * The database/connection pool
     */
    protected $_pool = [];

    /**
     * The default connection (usually the first connection defined)
     */
    protected $_default = '';

    /**
     * Create a fully prepared database object
     * @name    setConnection
     * @type    method
     * @access  public
     * @param   string connection reference
     * @param   string connection URI
     * @returns bool
     * @note    the URI is formatted like: scheme://user:pass@host[:port]/database
     *          providing an unique reference provides you to ability to use more than one connection
     */
    public function setConnection(string $reference, string $uri)
    {
        $reference = strtoupper($reference);
        $parsedUri = parse_url($uri);

        if ($this->_default === '') {
            $this->_default = $reference;
        }

        if (is_array($parsedUri) && array_key_exists('scheme', $parsedUri)) {
            $this->_pool[$reference] = $this->instance($parsedUri['scheme']);

            if (is_object($this->_pool[$reference])) {
                return $this->_pool[$reference]->setConnectionUri($uri);
            }
        }

        return false;
    }

    /**
     * Set the default DB connection, if it exists
     * @name    setDefaultConnection
     * @type    method
     * @access  public
     * @param   string connection reference
     * @returns string reference
     * @note    By default the first connection will be the default connection, a call to the setDefaultConnection
     *          is only required if you want to change this behaviour
     */
    public function setDefaultConnection($reference)
    {
        $reference = strtoupper($reference);

        if (array_key_exists($reference, $this->_pool) && is_object($this->_pool[$reference])) {
            return $this->_default = $reference;
        }

        return false;
    }

    /**
     * Connect a database/[scheme] instance
     * @name    connect
     * @type    method
     * @access  public
     * @returns bool
     * @syntax  connect();
     */
    public function connect()
    {
        if (array_key_exists($this->_default, $this->_pool) && is_object($this->_pool[$this->_default])) {
            return $this->_pool[$this->_default]->connect();
        }

        return false;
    }

    /**
     * Disconnect from the database.
     * @return bool success
     */
    public function disconnect(): bool
    {
        if ($this->isConnected() && array_key_exists($this->_default, $this->_pool) && !is_null($this->_pool[$this->_default]->connection)) {
            $closed = mysqli_close($this->_pool[$this->_default]->connection);
            if ($closed) {
                unset($this->_pool[$this->_default]->connection);
            }

            return $closed;
        }

        return true;
    }


    /**
     *  Verify whether a connection is established
     *  @name    isConnected
     *  @type    method
     *  @access  public
     *  @param   string reference
     *  @returns bool
     *  @syntax  isConnected( string reference );
     */
    public function isConnected()
    {
        if (array_key_exists($this->_default, $this->_pool) && is_object($this->_pool[ $this->_default ])) {
            return $this->_pool[ $this->_default ]->isConnected();
        }

        return false;
    }

    /**
     * Magic destructor, disconnects all DB connections
     * @name    __destruct
     * @type    method
     * @access  public
     */
    public function __destruct()
    {
        $this->disconnect(true);
    }

    /**
     * Magic __call, implicit method bridge to defined connections
     * @name    __call
     * @type    method
     * @access  public
     * @note    By default all calls which are not defined in this class are bridged to the default connection
     * @see     setDefaultConnection
     */
    public function __call($call, $arguments)
    {
        //  Get the first argument, which could be a reference to a pool item
        $reference = (string) array_shift($arguments);

        //  In case the first argument was not a pool item, put the first argument back in refer to the master
        if (!array_key_exists($reference, $this->_pool)) {
            array_unshift($arguments, $reference);
            $reference = $this->_default;
        }

        if (method_exists($this->_pool[$reference], $call)) {
            return call_user_func_array(
                array(
                    &$this->_pool[$reference], // the database object
                    $call // the method
                ),
                $arguments
          );
        }

        return parent::__call($call, $arguments);
    }
}
