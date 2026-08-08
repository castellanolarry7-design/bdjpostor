<?php
return [
    'paths'                    => ['api/*', 'broadcasting/auth', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    // Los orígenes salen de FRONTEND_URLS (lista separada por comas) para no
    // tener que tocar el código al cambiar de dominio. Nunca poner '*' aquí:
    // con supports_credentials en true, el navegador lo rechaza y además
    // permitiría que cualquier web hiciera peticiones autenticadas.
    'allowed_origins'          => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'FRONTEND_URLS',
            'http://localhost:5173,http://localhost:3000,http://127.0.0.1:5173,https://bdjpostor.onrender.com'
        ))
    ))),
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];
