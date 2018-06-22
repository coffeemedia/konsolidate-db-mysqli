<?php

declare(strict_types=1);

$konsolidatePath = '../../konsolidate';

require_once(sprintf('%s/konsolidate.class.php', $konsolidatePath));

$konsolidate = new Konsolidate(array(
    'KContribDB_' => '../src',
    'Core' => sprintf('%s/core', $konsolidatePath),
));

$mysqli = 'mysqli://phpunit:phpunit@localhost/konsolidate-db-mysql';
