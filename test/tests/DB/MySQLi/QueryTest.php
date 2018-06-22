<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DBMySQLiQueryTest extends TestCase
{
    public function testIsCorrectInstance(): void
    {
        global $konsolidate;

        $query = $konsolidate->get('/DB/MySQLi/Query');

        $this->assertInstanceOf(
            'KContribDB_DBMySQLiQuery',
            $query
        );
    }
}
