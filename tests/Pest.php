<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Pest runs the existing PHPUnit test classes unchanged; those classes pick
| their own base class and traits. The binding below applies only to
| function-style Pest test files (`test()` / `it()`), which is the style new
| module code (Tracking, Media) is written in.
|
| Feature tests get a fresh SQLite :memory: database per test, the same as the
| class-based Feature tests that `use RefreshDatabase`. A module's own tests
| (modules/<Name>/tests/{Feature,Unit}) get the same bindings.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../modules/*/tests/Feature');

pest()->extend(TestCase::class)
    ->in('Unit', '../modules/*/tests/Unit');
