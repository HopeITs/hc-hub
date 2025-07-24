<?php

namespace App\Livewire\Layouts;

use Livewire\Component;

class Breadcrumbs extends Component
{
    public string $title = 'Holidays';

    /** @var array<int, array{label: string, url: string|null}> */
    public array $crumbs = [
        ['label' => 'Hope Hr', 'url' => null],
        ['label' => 'Test', 'url' => null],
    ];

    public bool $showAddButton = true;
    public string $addModalTarget = 'target';
    public string $addLabel = 'Add Label';
    public string $addUrl = '#';

    public function render()
    {
        return view('livewire.layouts.breadcrumbs');
    }
}
