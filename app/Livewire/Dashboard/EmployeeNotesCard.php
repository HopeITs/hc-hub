<?php

namespace App\Livewire\Dashboard;

use App\Models\EmployeeNote;
use Livewire\Component;

class EmployeeNotesCard extends Component
{
    public int $employeeId;
    public string $newText = '';

    public function mount(int $employee): void
    {
        $this->employeeId = $employee;
    }

    public function getNotesProperty()
    {
        return EmployeeNote::forEmployee($this->employeeId)
            ->orderBy('order')
            ->get();
    }

    public function addText(): void
    {
        $content = trim($this->newText);

        if ($content === '') {
            $content = 'New Note';
        }

        $maxOrder = EmployeeNote::forEmployee($this->employeeId)->max('order') ?? 0;

        EmployeeNote::create([
            'employee_id' => $this->employeeId,
            'type'        => 'note',
            'content'     => $content,
            'is_done'     => false,
            'order'       => $maxOrder + 1,
            'color'       => '#111827',
        ]);

        $this->newText = '';
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

    public function delete(int $id): void
    {
        $this->findOwned($id)->delete();
    }

    private function findOwned(int $id): EmployeeNote
    {
        return EmployeeNote::forEmployee($this->employeeId)->findOrFail($id);
    }


    public function createNote(string $content, string $type): void
    {
        $content = trim($content);
        if ($content === '') return;

        $maxOrder = EmployeeNote::forEmployee($this->employeeId)->max('order') ?? 0;

        EmployeeNote::create([
            'employee_id' => $this->employeeId,
            'type'        => $type ?: 'note',
            'content'     => $content,
            'is_done'     => false,
            'order'       => $maxOrder + 1,
            'color'       => '#111827',
        ]);
    }


    public function render()
    {
        return view('livewire.dashboard.employee-notes-card', [
            'notes' => $this->notes,
        ]);
    }
}
