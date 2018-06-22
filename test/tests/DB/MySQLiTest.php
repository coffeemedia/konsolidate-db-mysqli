<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DBMySQLiTest extends TestCase
{
    public function testIsCorrectInstance(): void
    {
        global $konsolidate;

        $mysqli = $konsolidate->get('/DB/MySQLi');

        $this->assertInstanceOf(
            'KContribDB_DBMySQLi',
            $mysqli
        );
    }

    public function testSetConnection(): void
    {
        global $konsolidate;
        global $mysqli;

        $connection = $konsolidate('/DB/setConnection', 'mysqli', $mysqli);

        $this->assertArrayHasKey('scheme', $connection);
        $this->assertArrayHasKey('host', $connection);
        $this->assertArrayHasKey('user', $connection);
        $this->assertArrayHasKey('pass', $connection);
        $this->assertArrayHasKey('path', $connection);
    }

    public function testSetConnectionFalse(): void
    {
        global $konsolidate;

        $connection = $konsolidate('/DB/setConnection', 'mysqli-false', 'foo');

        $this->assertEquals(false, $connection);
    }

    public function testSetDefaultConnection(): void
    {
        global $konsolidate;
        global $mysqli;

        $DB = $konsolidate->instance('/DB');
        $connection1 = $DB->setConnection('first_connection', $mysqli); // Sets default connection implicit

        $this->assertEquals($DB->get('_default'), strtoupper('first_connection'));
        $this->assertEquals(count($DB->get('_pool')), 1);

        $connection1 = $DB->setConnection('second_connection', $mysqli);

        $this->assertEquals($DB->get('_default'), strtoupper('first_connection'));
        $this->assertEquals(count($DB->get('_pool')), 2);

        $DB->setDefaultConnection('second_connection'); // Sets default connection explicit

        $this->assertEquals($DB->get('_default'), strtoupper('second_connection'));
    }

    public function testConnectFalse(): void
    {
        global $konsolidate;
        global $mysqli;

        $DB = $konsolidate->instance('/DB');
        $isConnected = $DB->connect('foo');

        $this->assertEquals(false, $isConnected);
    }

    public function testIsConnectedTrue(): void
    {
        global $konsolidate;
        global $mysqli;

        $DB = $konsolidate->instance('/DB');
        $isConnected = $DB->isConnected();

        $this->assertEquals(false, $isConnected);

        $DB->setConnection('mysqli', $mysqli);
        $DB->connect('mysqli');
        $isConnected = $DB->isConnected();

        $this->assertEquals(true, $isConnected);
    }

    public function testDisconnect(): void
    {
        global $konsolidate;
        global $mysqli;

        $DB = $konsolidate->instance('/DB');
        $connection = $DB->setConnection('mysqli', $mysqli);
        $DB->connect('mysqli');
        $isConnected = $DB->isConnected();

        $this->assertEquals(true, $isConnected);

        $DB->disconnect();
        $isConnected = $DB->isConnected();

        $this->assertEquals(false, $isConnected);
    }

    public function testIsConnectedFalse(): void
    {
        global $konsolidate;

        $DB = $konsolidate->get('/DB');

        $this->assertEquals(false, $DB->isConnected());
    }
}
