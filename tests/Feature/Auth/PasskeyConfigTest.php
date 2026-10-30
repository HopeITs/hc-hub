<?php

use Laravel\Passkeys\Passkeys;

/**
 * Evaluate a config file as it would load with the given environment
 * variables (null removes one), restoring the real environment afterwards.
 *
 * @param  array<string, string|null>  $variables
 * @return array<string, mixed>
 */
function configFileWithEnv(string $file, array $variables): array
{
    $original = [];

    foreach ($variables as $name => $value) {
        $original[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];

        unset($_SERVER[$name], $_ENV[$name]);
        putenv($name);

        if ($value !== null) {
            $_SERVER[$name] = $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }

    try {
        return require config_path($file);
    } finally {
        foreach ($original as $name => [$server, $env, $putenv]) {
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($putenv === false ? $name : "{$name}={$putenv}");

            if ($server !== null) {
                $_SERVER[$name] = $server;
            }

            if ($env !== null) {
                $_ENV[$name] = $env;
            }
        }
    }
}

test('the passkey origin is the app url without its path or trailing slash', function (string $appUrl, string $origin) {
    config(['app.url' => $appUrl]);

    $fortify = configFileWithEnv('fortify.php', []);

    expect($fortify['passkeys']['allowed_origins'])->toBe([$origin]);
})->with([
    'local with a trailing slash' => ['http://127.0.0.1:8000/', 'http://127.0.0.1:8000'],
    'served under a path' => ['https://hub.example.org/app/', 'https://hub.example.org'],
    'already an origin' => ['https://hub.example.org', 'https://hub.example.org'],
]);

test('the configured passkey origins carry no path or trailing slash', function () {
    expect(Passkeys::allowedOrigins())
        ->not->toBeEmpty()
        ->each->toMatch('#^https?://[^/?\#]+$#');
});

test('an empty passkey user handle secret falls back to the app key', function (?string $secret) {
    $fortify = configFileWithEnv('fortify.php', ['PASSKEYS_USER_HANDLE_SECRET' => $secret]);

    expect($fortify['passkeys']['user_handle_secret'])
        ->toBe(config('app.key'))
        ->not->toBeEmpty();
})->with([
    'unset' => [null],
    'left empty as in .env.example' => [''],
]);

test('a configured passkey user handle secret is used instead of the app key', function () {
    $fortify = configFileWithEnv('fortify.php', ['PASSKEYS_USER_HANDLE_SECRET' => 'separate-passkey-secret']);

    expect($fortify['passkeys']['user_handle_secret'])->toBe('separate-passkey-secret');
});
