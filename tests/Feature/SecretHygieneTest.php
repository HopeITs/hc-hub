<?php

use Illuminate\Support\Facades\Process;

/**
 * The committed .env.example parsed into name => value, with surrounding
 * quotes removed and comment lines skipped.
 *
 * @return array<string, string>
 */
function envExampleValues(): array
{
    $values = [];

    foreach (file(base_path('.env.example'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $values[trim($name)] = trim(trim($value), '"\'');
    }

    return $values;
}

test('.env.example leaves every key, secret, password and token empty', function () {
    $filled = collect(envExampleValues())
        ->filter(fn (string $value, string $name): bool => preg_match('/(KEY|KEYS|SECRET|PASSWORD|TOKEN)(_ID)?$/', $name) === 1)
        ->reject(fn (string $value): bool => in_array($value, ['', 'null'], true) || preg_match('/^\$\{[A-Z_]+\}$/', $value) === 1)
        ->keys()
        ->all();

    expect($filled)->toBe([]);
});

test('.env.example names no Super Admin accounts', function () {
    // HR_ADMIN_EMAILS went with the HR database (one database, one login); any
    // admin-account key that comes back must stay empty in the template.
    $named = array_filter(envExampleValues(), fn (string $value, string $key): bool => preg_match('/ADMIN/', $key) === 1 && $value !== '', ARRAY_FILTER_USE_BOTH);

    expect($named)->toBe([]);
});

test('git ignores every environment file except the template', function (string $path, bool $ignored) {
    $result = Process::path(base_path())->run(['git', 'check-ignore', '--no-index', '--quiet', $path]);

    expect($result->exitCode())->toBe($ignored ? 0 : 1);
})->with([
    ['.env', true],
    ['.env.production', true],
    ['.env.staging', true],
    ['.env.local', true],
    ['.env.backup', true],
    ['.env.example', false],
]);
