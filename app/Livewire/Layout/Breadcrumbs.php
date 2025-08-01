<?php

namespace App\Livewire\Layout;

use Livewire\Component;

class Breadcrumbs extends Component
{
    public string $title = 'Page Title';

    /** @var array<int, array{label: string, url?: string|null}> */
    public array $crumbs = [];

    public bool $showButton = false;



    public function render()
    {
        return view('livewire.layout.breadcrumbs');
    }
}
