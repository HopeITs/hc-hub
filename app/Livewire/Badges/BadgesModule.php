<?php

namespace App\Livewire\Badges;

use App\Enums\BadgeSubtype;
use App\Enums\BadgeType;
use App\Models\Badge;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class BadgesModule extends Component
{
    public bool $showModal = false;

    public string $modalTitle = '';

    #[Locked]
    public array $badge = [];

    protected $listeners = [
        'open-badge-modal' => 'openBadgeModal',
    ];

    public function boot(): void
    {
        Gate::authorize('viewAny', Badge::class);
    }

    public function openBadgeModal(int $id): void
    {
        $b = Badge::query()
            ->visibleTo(auth()->user())
            ->with([
                'employee',
                'evaluation',
                'employee.contracts.position.program',
                'employee.contracts.position.department',
            ])
            ->find($id);

        abort_unless($b, 404);

        Gate::authorize('view', $b);

        $this->badge = [
            'id' => $b->id,
            'employee' => $b->employee?->name,
            'type' => BadgeType::getLocalizedName((int) $b->type),
            'detail_label' => BadgeSubtype::selectorLabelForType($b->type),
            'subtype' => $b->subtype ? BadgeSubtype::getLocalizedName($b->subtype) : null,
            'date' => optional($b->date)->format('Y-m-d'),
            'reason' => $b->reason,
            'amount' => $b->amount,
            'new_position' => $b->new_position,
            'leave_unit' => $this->leaveUnitLabel($b->leave_unit),
            'leave_amount_label' => $this->leaveAmountLabel($b->leave_unit),
            'currency' => $this->currencyLabel($b->currency),
            'current_salary' => $b->current_salary,
            'new_salary' => $b->new_salary,
            'notes' => $b->notes,
            'program' => $b->employee?->latestActiveContract()?->position?->program?->name,
            'department' => $b->employee?->latestActiveContract()?->position?->department?->name,
            'is_salary' => (int) $b->type === BadgeType::Salary_Increase,
            'is_financial_detail' => BadgeSubtype::isFinancial($b->subtype),
            'is_promotion' => BadgeSubtype::isPromotion($b->subtype),
            'is_leave' => BadgeSubtype::isLeave($b->subtype),
        ];

        $this->modalTitle = __('general.badges');
        $this->showModal = true;
    }

    public function render()
    {
        return view('livewire.badges.badges-module');
    }

    protected function currencyLabel($v): string
    {
        return match ((int) ($v ?? 0)) {
            1 => 'SYP',
            2 => 'USD',
            3 => 'EUR',
            default => trans('general.unknown'),
        };
    }

    protected function leaveUnitLabel(?string $value): ?string
    {
        return match ($value) {
            'hour' => trans('general.leave-hours'),
            'day' => trans('general.leave-days'),
            default => null,
        };
    }

    protected function leaveAmountLabel(?string $value): string
    {
        return match ($value) {
            'hour' => trans('general.number-of-hours'),
            'day' => trans('general.number-of-days'),
            default => trans('general.leave-quantity'),
        };
    }
}
