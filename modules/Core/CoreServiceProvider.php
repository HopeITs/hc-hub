<?php

declare(strict_types=1);

namespace Modules\Core;

use Illuminate\Support\ServiceProvider;

/**
 * Data every module shares: User and Program now; City and Center join in a
 * later phase. Core has no pages or routes of its own yet, only the `core::`
 * view namespace for when it does.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'core');
    }
}
