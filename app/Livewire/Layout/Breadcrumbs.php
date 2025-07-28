<?php

namespace App\Livewire\Layout;

use Livewire\Component;

class Breadcrumbs extends Component
{
    public string $title = 'Page Title';

    /** @var array<int, array{label: string, url?: string|null}> */
    public array $crumbs = [];

    public bool $showButton = false;
    public string $buttonLabel = 'Add';
    public string $buttonIcon = 'fa-solid fa-circle-plus';
    public string $buttonUrl = '';
    public string $buttonModalTarget = '';
    public string $buttonModalToggle = '';

    public function render()
    {
        return view('livewire.layout.breadcrumbs');
    }
}
