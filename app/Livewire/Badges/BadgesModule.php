<?php

namespace App\Livewire\Badges;

use App\Models\Badge;
use Livewire\Component;

class BadgesModule extends Component
{
    public bool $showModal = false;
    public string $modalTitle = '';
    public array $badge = [];

    public function openBadge(int $id): void
    {
        $b = Badge::with([
            'employee',
            'evaluation',
            'employee.contracts.position.program',
            'employee.contracts.position.department',
        ])->findOrFail($id);

        $this->badge = [
            'id' => $b->id,
            'employee' => $b->employee?->name,
            'type' => \App\Enums\BadgeType::getLocalizedName((int)$b->type),
            'date' => optional($b->date)->format('Y-m-d'),
            'reason' => $b->reason,
            'currency' => $this->currencyLabel($b->currency),
            'current_salary' => $b->current_salary,
            'new_salary' => $b->new_salary,
            'notes' => $b->notes,
            'program' => $b->employee?->latestActiveContract()?->position?->program?->name,
            'department' => $b->employee?->latestActiveContract()?->position?->department?->name,
        ];

        $this->modalTitle = __('general.badges') . ' #' . $b->id;
        $this->showModal = true;
    }

    public function render()
    {
        return view('livewire.badges.badges-module');
    }

    protected function currencyLabel($v): string
    {
        return match ((int)($v ?? 0)) {
            1 => 'SYP',
            2 => 'USD',
            3 => 'EUR',
            default => trans('general.unknown')
        };
    }
}
