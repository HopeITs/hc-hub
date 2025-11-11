<?php

namespace App\Livewire\Dashboard;

use App\Models\Contract;
use Livewire\Component;
use Carbon\Carbon;

class SoonEndingContractsCard extends Component
{
    public function render()
    {
        $today = Carbon::today();
        $limit = $today->copy()->addDays(30);

        $contracts = Contract::query()
            ->where('is_active', true)
            ->whereNull('terminated_at')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $limit)
            ->with(['employee', 'employee.city', 'position.department'])
            ->orderBy('end_date', 'asc')
            ->limit(5)
            ->get();

        $contracts->each(function ($contract) {
            $end = Carbon::parse($contract->end_date)->startOfDay();
            $contract->days_left = Carbon::today()->diffInDays($end, false);

            $employee = $contract->employee;

            $contract->employee_name = $employee?->name ?? 'N/A';
            $contract->city_name = $employee?->city?->name ?? 'N/A';
            $contract->department_title = $contract->position?->department?->name ?? 'N/A';
            $contract->position_title = $contract->position?->title ?? '';
            $contract->employee_photo = $employee?->photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($contract->employee_name) . '&color=7F9CF5&background=EBF4FF';
            $contract->view_route = route('contracts.show', ['contract' => $contract->id]);
        });

        return view('livewire.dashboard.soon-ending-contracts-card', [
            'contracts' => $contracts,
        ]);
    }
}
