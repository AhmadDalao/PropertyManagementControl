<?php

$hostingerPhp = '/opt/alt/php84/usr/bin/php';
$systemTar = '/usr/bin/tar';

return [
    'scheduler_php_binary' => env(
        'SCHEDULER_PHP_BINARY',
        is_executable($hostingerPhp) ? $hostingerPhp : PHP_BINARY,
    ),
    'tar_binary' => env(
        'BACKUP_TAR_BINARY',
        is_executable($systemTar) ? $systemTar : 'tar',
    ),
    'backup_retention_count' => (int) env('BACKUP_RETENTION_COUNT', 7),
    'daily_report_retention_days' => (int) env('DAILY_REPORT_RETENTION_DAYS', 90),
    'daily_report_schedule' => env('DAILY_REPORT_SCHEDULE', '06:00'),
];
