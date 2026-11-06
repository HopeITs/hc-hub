<?php

use App\Support\Desk\Money;
use App\Support\StrategyRollup;

test('the verdict reads the share of targets on pace', function (int $on, int $total, string $key, string $tone, int $behind) {
    expect(StrategyRollup::verdict($on, $total))->toBe(['key' => $key, 'tone' => $tone, 'on' => $on, 'total' => $total, 'behind' => $behind]);
})->with([
    '95 of 100 is on track' => [95, 100, 'on_track', 'good', 5],
    '94 of 100 is mostly on track' => [94, 100, 'mostly_on_track', 'warn', 6],
    '80 of 100 is still mostly on track' => [80, 100, 'mostly_on_track', 'warn', 20],
    '79 of 100 is off track' => [79, 100, 'off_track', 'critical', 21],
    '33 of 35 is mostly on track' => [33, 35, 'mostly_on_track', 'warn', 2],
    '35 of 35 is on track with nobody behind' => [35, 35, 'on_track', 'good', 0],
]);

test('no rated targets means no verdict', function () {
    expect(StrategyRollup::verdict(0, 0))->toBeNull();
});

test('a program is behind below four in five targets on pace', function (?array $counts, ?string $pace) {
    expect(StrategyRollup::programPace($counts))->toBe($pace);
})->with([
    'nothing rated' => [null, null],
    'rated zero' => [['on' => 0, 'total' => 0], null],
    '80 of 100' => [['on' => 80, 'total' => 100], 'on_pace'],
    '79 of 100' => [['on' => 79, 'total' => 100], 'behind'],
]);

test('compact money shortens the amount after the same currency prefix', function (int|float $amount, string $currency, string $expected) {
    expect(Money::compact($amount, $currency))->toBe($expected);
})->with([
    'euro millions' => [3960000, 'EUR', '€3.96M'],
    'dollar thousands' => [420000, 'USD', '$420K'],
    'other currency code' => [1500, 'SYP', 'SYP 1.5K'],
]);
