<?php

namespace App\Livewire\EmployeeCards;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ShowDigitalId extends Component
{
    public Employee $employee;

    public function mount(string $username): void
    {
        $this->employee = Employee::query()
            ->where('username', $username)
            ->with([
                'media',
                'contracts' => fn ($query) => $query
                    ->with([
                        'position.center.city',
                        'position.city',
                        'position.department',
                    ])
                    ->latest('start_date'),
            ])
            ->firstOrFail();
    }

    public function render()
    {
        $contract = $this->currentContract();
        $position = $contract?->position;
        $city = $position?->city ?? $position?->center?->city;
        $emergencyPhone = $this->findEmergencyPhone($position);

        return view('livewire.employee-cards.show-digital-id', [
            'employee' => $this->employee,
            'avatarSrc' => $this->employee->getFirstMediaUrl('avatar'),
            'position' => $position,
            'center' => $position?->center,
            'city' => $city,
            'emergencyPhone' => $emergencyPhone,
            'emergencyPhoneHref' => $this->phoneHref($emergencyPhone),
            'phoneHref' => $this->phoneHref($this->employee->work_phone),
            'telegramHref' => $this->telegramHref($this->employee->telegram_work),
            'linkedinHref' => $this->linkedinHref($this->employee->linkedin),
        ]);
    }

    private function currentContract(): ?Contract
    {
        return $this->employee->contracts->first();
    }

    private function findEmergencyPhone(?Position $position): ?string
    {
        $cityId = $position?->city_id;

        if (! $cityId) {
            return null;
        }

        $hrEmployee = Employee::query()
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query
                    ->where(function (Builder $query) {
                        $query
                            ->whereNotNull('work_phone')
                            ->where('work_phone', '<>', '');
                    })
                    ->orWhere(function (Builder $query) {
                        $query
                            ->whereNotNull('personal_phone')
                            ->where('personal_phone', '<>', '');
                    });
            })
            ->whereHas('contracts', function (Builder $contract) use ($cityId) {
                $contract->whereHas('position', fn (Builder $position) => $this->scopeHrPositionInCity($position, $cityId));
            })
            ->orderBy('id')
            ->first(['id', 'work_phone', 'personal_phone']);

        return $hrEmployee?->work_phone ?: $hrEmployee?->personal_phone;
    }

    private function scopeHrPositionInCity(Builder $query, int $cityId): void
    {
        $query
            ->where('city_id', $cityId)
            ->where(function (Builder $query) {
                $query
                    ->where('title_en', 'like', '%HR%')
                    ->orWhere('title_en', 'like', '%Human Resource%')
                    ->orWhere('title_ar', 'like', '%الموارد البشرية%')
                    ->orWhereHas('department', function (Builder $department) {
                        $department
                            ->where('code', 'HR')
                            ->orWhere('name', 'like', '%Human Resource%')
                            ->orWhere('name', 'like', '%HR%');
                    });
            });
    }

    private function phoneHref(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        return 'tel:'.preg_replace('/[^\d+]/', '', $phone);
    }

    private function telegramHref(?string $telegram): ?string
    {
        $telegram = trim((string) $telegram);

        if ($telegram === '') {
            return null;
        }

        if (str_starts_with($telegram, 'http://') || str_starts_with($telegram, 'https://')) {
            return $telegram;
        }

        return 'https://t.me/'.ltrim($telegram, '@');
    }

    private function linkedinHref(?string $linkedin): ?string
    {
        $linkedin = trim((string) $linkedin);

        if ($linkedin === '') {
            return null;
        }

        if (str_starts_with($linkedin, 'http://') || str_starts_with($linkedin, 'https://')) {
            return $linkedin;
        }

        return 'https://www.linkedin.com/in/'.trim($linkedin, '/');
    }
}
