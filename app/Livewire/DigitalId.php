<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class DigitalId extends Component
{
    public Employee $employee;

    public function mount(string $username): void
    {
        $this->employee = Employee::query()
            ->where('username', $username)
            ->with([
                'media',
                'contracts.position.center.city',
                'contracts.position.city',
                'contracts.position.department',
            ])
            ->firstOrFail();
    }

    public function render()
    {
        $currentContract = $this->employee->latestActiveContract();
        $currentContract?->loadMissing([
            'position.center.city',
            'position.city',
            'position.department',
        ]);

        $position = $currentContract?->position;
        $city = $position?->city ?? $position?->center?->city;

        return view('livewire.digital-id', [
            'employee' => $this->employee,
            'avatarSrc' => $this->employee->getFirstMediaUrl('avatar'),
            'position' => $position,
            'center' => $position?->center,
            'city' => $city,
            'emergencyPhone' => $this->findEmergencyPhone($position),
            'phoneHref' => $this->phoneHref($this->employee->work_phone),
            'telegramHref' => $this->telegramHref($this->employee->telegram_work),
            'linkedinHref' => $this->linkedinHref($this->employee->linkedin),
        ]);
    }

    private function findEmergencyPhone(?Position $position): ?string
    {
        $cityId = $position?->city_id ?? $position?->center?->city_id;

        if (! $cityId) {
            return null;
        }

        return Employee::query()
            ->where('is_active', true)
            ->whereNotNull('work_phone')
            ->where('work_phone', '<>', '')
            ->whereHas('contracts', function (Builder $contract) use ($cityId) {
                $contract
                    ->where('is_active', true)
                    ->whereNull('terminated_at')
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->whereHas('position', function (Builder $position) use ($cityId) {
                        $position
                            ->where(function (Builder $cityScope) use ($cityId) {
                                $cityScope
                                    ->where('city_id', $cityId)
                                    ->orWhereHas('center', fn (Builder $center) => $center->where('city_id', $cityId));
                            })
                            ->where(function (Builder $hrScope) {
                                $hrScope
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
                    });
            })
            ->oldest('id')
            ->value('work_phone');
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
