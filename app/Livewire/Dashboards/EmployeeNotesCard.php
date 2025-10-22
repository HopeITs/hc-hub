<?php

namespace App\Livewire\Dashboards;

use App\Models\EmployeeNote;
use Livewire\Attributes\On;
use Livewire\Component;

class EmployeeNotesCard extends Component
{
    public int $employeeId;
    public string $newText = '';
    public string $newCheckbox = '';
    public string $filter = 'all';
    public string $search = '';

    public array $palette = [
        '#111827', '#1f2937', '#374151', '#4338ca', '#2563eb',
        '#059669', '#16a34a', '#d97706', '#dc2626', '#be185d',
        '#6b7280', '#0f766e', '#7c3aed', '#be1f24', '#98191d',
        '#731316', '#4d0d0f', '#2a0808', '#150404',
        '#414042', '#6d6e71', '#939598', '#dcddde', '#f5f5f6', '#2b2a2c'
    ];

    public function mount(int $employee)
    {
        $this->employeeId = $employee;
    }

    public function getNotesProperty()
    {
        $q = EmployeeNote::forEmployee($this->employeeId)->orderBy('order');

        if ($this->filter === 'open')   $q->where('is_done', false);
        if ($this->filter === 'done')   $q->where('is_done', true);
        if ($this->search !== '')       $q->where('content', 'like', '%'.$this->search.'%');

        return $q->get();
    }

    public function addText(): void
    {
        if (trim($this->newText) === '') return;

        $maxOrder = EmployeeNote::forEmployee($this->employeeId)->max('order') ?? 0;

        EmployeeNote::create([
            'employee_id' => $this->employeeId,
            'type'        => 'text',
            'content'     => trim($this->newText),
            'order'       => $maxOrder + 1,
            'color'       => '#111827',
        ]);

        $this->newText = '';
    }

    public function addCheckbox(): void
    {
        if (trim($this->newCheckbox) === '') return;

        $maxOrder = EmployeeNote::forEmployee($this->employeeId)->max('order') ?? 0;

        EmployeeNote::create([
            'employee_id' => $this->employeeId,
            'type'        => 'checkbox',
            'content'     => trim($this->newCheckbox),
            'is_done'     => false,
            'order'       => $maxOrder + 1,
            'color'       => '#111827',
        ]);

        $this->newCheckbox = '';
    }

    public function toggleDone(int $id): void
    {
        $note = $this->findOwned($id);
        $note->update(['is_done' => !$note->is_done]);
    }

    public function updateContent(int $id, string $content): void
    {
        $note = $this->findOwned($id);
        $note->update(['content' => trim($content)]);
    }

    public function updateColor(int $id, string $color): void
    {
        $note = $this->findOwned($id);

        $note->update(['color' => $color]);

        $this->dispatch('$refresh');
    }

    public function delete(int $id): void
    {
        $this->findOwned($id)->delete();
    }

    #[On('reorder-notes')]
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $i => $id) {
            EmployeeNote::forEmployee($this->employeeId)
                ->where('id', $id)
                ->update(['order' => $i + 1]);
        }
    }

    private function findOwned(int $id): EmployeeNote
    {
        return EmployeeNote::forEmployee($this->employeeId)->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.dashboards.employee-notes-card', [
            'notes' => $this->notes,
        ]);
    }
}
