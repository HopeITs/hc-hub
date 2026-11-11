<?php

declare(strict_types=1);

namespace App\Support\Blaze;

use Livewire\Blaze\Folder\Folder;
use Livewire\Blaze\Support\ComponentSource;

/**
 * Blaze's folder with folding switched off for every component, including
 * Flux's own `@blaze(fold: true)` templates. Folding evaluates `__()` at
 * compile time and bakes one locale into the cached view (English users saw
 * Arabic labels), so the hub only uses Blaze's compile step, which turns
 * anonymous components into plain PHP functions and keeps translations
 * per request.
 */
class NonFoldingFolder extends Folder
{
    protected function shouldFold(ComponentSource $source): bool
    {
        return false;
    }
}
