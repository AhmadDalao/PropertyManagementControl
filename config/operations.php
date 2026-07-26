<?php

$hostingerPhp = '/opt/alt/php84/usr/bin/php';

return [
    'scheduler_php_binary' => env(
        'SCHEDULER_PHP_BINARY',
        is_executable($hostingerPhp) ? $hostingerPhp : PHP_BINARY,
    ),
];
