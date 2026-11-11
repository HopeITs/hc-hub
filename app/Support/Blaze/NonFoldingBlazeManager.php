<?php

declare(strict_types=1);

namespace App\Support\Blaze;

use Illuminate\View\Compilers\BladeCompiler;
use Livewire\Blaze\BladeService;
use Livewire\Blaze\BlazeManager;
use Livewire\Blaze\Config;
use Livewire\Blaze\Runtime\BlazeRuntime;

/**
 * BlazeManager builds its Folder with `new`, so the only way to swap in the
 * non-folding one is to replace it after the parent constructor ran.
 * Registered in AppServiceProvider::register().
 */
class NonFoldingBlazeManager extends BlazeManager
{
    public function __construct(Config $config, BladeCompiler $bladeCompiler, BlazeRuntime $runtime, BladeService $blade)
    {
        parent::__construct($config, $bladeCompiler, $runtime, $blade);

        $this->folder = new NonFoldingFolder($config, $this->blade, $this->renderer, $this);
    }
}
