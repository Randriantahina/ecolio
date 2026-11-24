<?php

declare(strict_types=1);

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Only the SPA may call the API from a browser. Never '*': it would let any website use a stolen token.
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 600,

    // Auth uses bearer tokens, not cookies, so credentialed requests stay off.
    'supports_credentials' => false,
];
