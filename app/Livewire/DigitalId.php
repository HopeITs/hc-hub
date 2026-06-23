<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Facades\DB;
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
        $emergencyPhone = $this->findEmergencyPhone($position);

        return view('livewire.digital-id', [
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

    private function findEmergencyPhone(?Position $position): ?string
    {
        $cityId = $position?->city_id;

        if (! $cityId) {
            return null;
        }

        return $this->hrPhoneForCity($cityId)
            ?? $this->hrPhoneForCity($this->headOfficeCityId(), exceptCityId: $cityId)
            ?? $this->hrPhoneForCity();
    }

    private function hrPhoneForCity(?int $cityId = null, ?int $exceptCityId = null): ?string
    {
        $hr = DB::table('employees as e')
            ->join('contracts as c', 'c.employee_id', '=', 'e.id')
            ->join('positions as p', 'p.id', '=', 'c.position_id')
            ->leftJoin('departments as d', 'd.id', '=', 'p.department_id')
            ->where('e.is_active', true)
            ->where('c.is_active', true)
            ->whereDate('c.start_date', '<=', now())
            ->whereDate('c.end_date', '>=', now())
            ->where(function ($query) {
                $query
                    ->whereNotNull('e.work_phone')
                    ->where('e.work_phone', '<>', '')
                    ->orWhere(function ($query) {
                        $query
                            ->whereNotNull('e.personal_phone')
                            ->where('e.personal_phone', '<>', '');
                    });
            })
            ->when($cityId, fn ($query) => $query->where('p.city_id', $cityId))
            ->when($exceptCityId, fn ($query) => $query->where('p.city_id', '<>', $exceptCityId))
            ->where(function ($query) {
                $query
                    ->where('p.title_en', 'like', '%HR%')
                    ->orWhere('p.title_en', 'like', '%Human Resource%')
                    ->orWhere('p.title_ar', 'like', '%الموارد البشرية%')
                    ->orWhere('d.code', 'HR')
                    ->orWhere('d.name', 'like', '%Human Resource%')
                    ->orWhere('d.name', 'like', '%HR%');
            })
            ->orderBy('e.id')
            ->first(['e.work_phone', 'e.personal_phone']);

        return $hr?->work_phone ?: $hr?->personal_phone;
    }

    private function headOfficeCityId(): ?int
    {
        return DB::table('cities')
            ->where('slug', 'SYR')
            ->value('id');
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
