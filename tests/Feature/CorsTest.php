<?php

declare(strict_types=1);

it('lets the SPA call the API from its own origin', function () {
    $origin = config('app.frontend_url');

    $this->call('OPTIONS', '/api/v1/auth/login', [], [], [], [
        'HTTP_ORIGIN' => $origin,
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization,content-type',
    ])
        ->assertSuccessful()
        ->assertHeader('Access-Control-Allow-Origin', $origin);
});

it('does not open the API to other origins', function () {
    $response = $this->call('OPTIONS', '/api/v1/auth/login', [], [], [], [
        'HTTP_ORIGIN' => 'https://evil.example',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ]);

    // With a single configured origin the library still answers with that origin; the browser then rejects the
    // mismatch. What must never happen is echoing the foreign origin or answering with a wildcard.
    expect($response->headers->get('Access-Control-Allow-Origin'))
        ->not->toBe('https://evil.example')
        ->not->toBe('*');
});

it('allows the bearer token header, since auth is token based and not cookie based', function () {
    $response = $this->call('OPTIONS', '/api/v1/me', [], [], [], [
        'HTTP_ORIGIN' => config('app.frontend_url'),
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization',
    ]);

    expect(strtolower((string) $response->headers->get('Access-Control-Allow-Headers')))->toContain('authorization');
});
