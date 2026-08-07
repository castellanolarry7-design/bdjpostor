<?php
return [
    'driver'          => env('SESSION_DRIVER', 'file'),
    'lifetime'        => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt'         => false,
    'files'           => storage_path('framework/sessions'),
    'table'           => 'sessions',
    'lottery'         => [2, 100],
    'cookie'          => 'jpstore_session',
    'path'            => '/',
    'domain'          => env('SESSION_DOMAIN'),
    'secure'          => env('SESSION_SECURE_COOKIE'),
    'http_only'       => true,
    'same_site'       => 'lax',
];
