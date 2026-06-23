<?php

namespace App\Livewire\Position;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class ProfileDrawer extends Component
{
    public ?int $employeeId = null;

    public bool $open = false;

    /** Open the drawer for an employee (dispatched from any card on the page). */
    #[On('open-profile')]
    public function openProfile(int $employeeId): void
    {
        $this->employeeId = $employeeId;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    /** The employee being shown, with avatar + home city loaded. */
    public function getEmployeeProperty(): ?Employee
    {
        if (! $this->employeeId) {
            return null;
        }

        return Employee::with(['media', 'city'])->find($this->employeeId);
    }

    /** The employee's current position with all the lookups the drawer shows. */
    public function getPositionProperty(): ?Position
    {
        $contract = $this->employee?->latestActiveContract();

        if (! $contract) {
            return null;
        }

        return Position::with([
            'department', 'center', 'program', 'city', 'jobLevel',
            'directManager.activeContracts.employee.media',
            'directManager.jobLevel',
        ])->find($contract->position_id);
    }

    /** Direct reports (positions reporting to this position, with their holders). */
    public function getDirectReportsProperty(): Collection
    {
        if (! $this->position) {
            return collect();
        }

        return Position::query()
            ->visible()
            ->where('direct_manager_id', $this->position->id)
            ->with(['activeContracts.employee.media', 'jobLevel'])
            ->orderByGrade()
            ->get();
    }

    public function render()
    {
        return view('livewire.position.profile-drawer');
    }
}
