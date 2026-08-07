<?php
return [
    'default'  => env('LOG_CHANNEL', 'single'),
    'channels' => [
        'single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'debug')],
        'daily'  => ['driver' => 'daily', 'path' => storage_path('logs/laravel.log'), 'level' => env('LOG_LEVEL', 'debug'), 'days' => 14],
        'null'   => ['driver' => 'monolog', 'handler' => Monolog\Handler\NullHandler::class],
        'stack'  => ['driver' => 'stack', 'channels' => ['single']],
    ],
];
