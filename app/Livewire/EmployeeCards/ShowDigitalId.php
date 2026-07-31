<?php

namespace App\Livewire\EmployeeCards;

use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ShowDigitalId extends Component
{
    public Employee $employee;

    public function boot(): void
    {
        App::setLocale('en');
    }

    public function mount(string $token): void
    {
        $this->employee = Employee::query()
            ->where('digital_id_token', $token)
            ->where('is_active', true)
            // A deactivated employee's card, and a card that has been
            // explicitly disabled after being lost, both stop resolving here.
            ->where('digital_id_enabled', true)
            ->with([
                'city',
                'media',
                'contracts' => fn ($query) => $query
                    ->with([
                        'position.center.city',
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
        $city = $this->employee->city;
        $emergencyPhone = $this->findEmergencyPhone($this->employee->city_id);
        $firstName = $this->employee->first_name_en;
        $lastName = $this->employee->last_name_en;

        return view('livewire.employee-cards.show-digital-id', [
            'employee' => $this->employee,
            // The card is unauthenticated, so the internal avatar route would
            // 403 here. The bearer token has already been matched to an active
            // employee in mount(), which is what authorizes this signed link.
            'avatarSrc' => $this->employee->signedAvatarUrl(),
            'employeeName' => trim($firstName.' '.$lastName),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'position' => $position,
            'positionTitle' => $position?->title_en,
            'center' => $position?->center,
            'city' => $city,
            'cityName' => $city?->name_en,
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

    private function findEmergencyPhone(?int $cityId): ?string
    {
        if (! $cityId) {
            return null;
        }

        return Employee::query()
            ->where('is_active', true)
            ->where('city_id', $cityId)
            ->whereNotNull('work_phone')
            ->where('work_phone', '<>', '')
            ->whereHas('contracts.position.department', fn (Builder $department) => $department->where('code', 'HR'))
            ->orderBy('id')
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
