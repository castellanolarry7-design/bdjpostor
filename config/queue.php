<?php
return [
    'default'     => env('QUEUE_CONNECTION', 'sync'),
    'connections' => [
        'sync'     => ['driver' => 'sync'],
        'database' => ['driver' => 'database', 'table' => 'jobs', 'queue' => 'default', 'retry_after' => 90],
        'null'     => ['driver' => 'null'],
    ],
    'failed' => ['driver' => 'database-uuids', 'database' => env('DB_CONNECTION', 'sqlite'), 'table' => 'failed_jobs'],
];
