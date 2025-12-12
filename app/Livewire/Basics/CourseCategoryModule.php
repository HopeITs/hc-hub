<?php

namespace App\Livewire\Basics;

use App\Models\CourseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class CourseCategoryModule extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public bool $showModal = false;
    public string $modalTitle = 'Modal title';

    public array $item = [
        'id' => null,
        'name_en' => '',
        'name_ar' => '',
    ];

    protected array $rules = [
        'item.name_en' => ['required', 'string', 'max:255'],
        'item.name_ar' => ['required', 'string', 'max:255'],
    ];

    protected $listeners = ['delete' => 'delete', 'refreshTable' => '$refresh'];

    public function mount(): void
    {
        $this->resetForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $cat = CourseCategory::findOrFail($id);

        $this->item = [
            'id' => $cat->id,
            'name_en' => (string) ($cat->name_en ?? ''),
            'name_ar' => (string) ($cat->name_ar ?? ''),
        ];

        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if (!empty($this->item['id'])) {
            CourseCategory::findOrFail($this->item['id'])->update([
                'name_en' => $this->item['name_en'],
                'name_ar' => $this->item['name_ar'],
            ]);
        } else {
            CourseCategory::create([
                'name_en' => $this->item['name_en'],
                'name_ar' => $this->item['name_ar'],
            ]);
        }

        $this->showModal = false;
        $this->resetForm();

        $this->dispatch('notify', type: 'success', message: __('Saved successfully'));
        $this->dispatch('refreshTable');
    }

    public function backToIndex(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete(array $payload): void
    {
        $id = (int) ($payload['id'] ?? 0);
        $cat = CourseCategory::withCount('courses')->findOrFail($id);

        if ($cat->courses_count > 0) {
            $this->dispatch('notify', type: 'error', message: __('Cannot delete: category has courses.'));
            return;
        }

        $cat->delete();

        if ($this->categories->count() === 0 && $this->page > 1) {
            $this->previousPage();
        }

        $this->dispatch('notify', type: 'success', message: __('Deleted successfully'));
        $this->dispatch('refreshTable');
    }

    public function getCategoriesProperty()
    {
        return CourseCategory::query()
            ->withCount('courses')
            ->when($this->search, function (Builder $q) {
                $q->where(function (Builder $qq) {
                    $qq->where('name_en', 'like', "%{$this->search}%")
                       ->orWhere('name_ar', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name_en')
            ->paginate($this->perPage);
    }

    protected function resetForm(): void
    {
        $this->item = [
            'id' => null,
            'name_en' => '',
            'name_ar' => '',
        ];
    }

    public function render(): View
    {
        return view('livewire.basics.course-category-module', [
            'categories' => $this->categories,
        ])->title(__('Course Categories'));
    }
}
