<?php

namespace App\Livewire\ProgramManagement\Center;

use App\Models\Center;
use App\Models\ContractType;
use App\Models\Program;
use Livewire\Component;

class CenterDetails extends Component
{
    public Center $center;
    public string $activeTab = 'contracts';
    public array $contractData = [];
    public array $programData = [];

    public function mount(Center $center): void
    {
        $this->center = $center->load('city');
        $this->loadContractData();
        $this->loadProgramData();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Load employee counts by contract type for this center.
     */
    protected function loadContractData(): void
    {
        $locale = app()->getLocale();

        // Fetch all active contracts for this center
        $contracts = $this->center->positions()
            ->with([
                'contracts' => function ($query) {
                    $query->where('is_active', true)
                        ->whereDate('start_date', '<=', now())
                        ->whereDate('end_date', '>=', now());
                },
                'contracts.contractType'
            ])
            ->get()
            ->pluck('contracts')
            ->flatten();

        // Group contracts by contract_type_id and count unique employees
        $grouped = $contracts->groupBy('contract_type_id')
            ->map(fn($group) => $group->unique('employee_id')->count());

        if ($grouped->isEmpty()) {
            $this->contractData = [];
            return;
        }

        // Load all related contract types
        $contractTypes = ContractType::whereIn('id', $grouped->keys())
            ->get()
            ->keyBy('id');

        // Prepare groups (card titles are the only thing written by hand)
        $data = [
            'annual' => [
                'label' => $locale === 'ar' ? 'عقد سنوي' : 'Annual',
                'items' => [],
            ],
            'service' => [
                'label' => $locale === 'ar' ? 'عقد اتفاق خدمة' : 'Service',
                'items' => [],
            ],
            'trial' => [
                'label' => $locale === 'ar' ? 'عقد تجريبي' : 'Trial',
                'items' => [],
            ],
        ];

        foreach ($grouped as $typeId => $count) {
            /** @var \App\Models\ContractType|null $ct */
            $ct = $contractTypes->get($typeId);
            if (!$ct) {
                continue;
            }

            // Use English name to detect the group (annual / service / trial)
            $nameEn = strtolower($ct->name_en ?? '');
            $displayName = $locale === 'ar'
                ? ($ct->name_ar ?: $ct->name_en)
                : ($ct->name_en ?: $ct->name_ar);

            $groupKey = null;
            if (str_contains($nameEn, 'annual')) {
                $groupKey = 'annual';
            } elseif (str_contains($nameEn, 'agreement')) {
                $groupKey = 'service';
            } elseif (str_contains($nameEn, 'trial')) {
                $groupKey = 'trial';
            }

            // Unknown type → skip or put in a separate group if you like
            if (!$groupKey) {
                continue;
            }

            $data[$groupKey]['items'][] = [
                'label' => $displayName,
                'count' => $count,
            ];
        }

        // Remove empty groups so you don't get empty cards
        $this->contractData = array_filter(
            $data,
            fn ($group) => !empty($group['items'])
        );
    }



    /**
     * Load employee counts by program for this center.
     */
    protected function loadProgramData(): void
    {
        $positions = $this->center->positions()
            ->with(['contracts' => function ($query) {
                $query->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now());
            }, 'program'])
            ->get();

        $programCounts = [];

        foreach ($positions as $position) {
            $programName = $position->program?->name ?? $position->program?->name_en ?? $position->program?->name_ar ?? 'Unknown Program';
            $activeContracts = $position->contracts->unique('employee_id')->count();
            $programCounts[$programName] = ($programCounts[$programName] ?? 0) + $activeContracts;
        }

        $this->programData = collect($programCounts)
            ->map(fn($count, $program) => ['program' => $program, 'count' => $count])
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.program-management.center-details');
    }
}
