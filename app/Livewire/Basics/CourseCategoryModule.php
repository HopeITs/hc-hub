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

    public ?int $editingId = null;
    public string $name_en = '';
    public string $name_ar = '';

    public bool $showFormModal = false;
    public bool $isCreatePage = false;

    protected array $rules = [
        'name_en' => ['required', 'string', 'max:255'],
        'name_ar' => ['required', 'string', 'max:255'],
    ];

    protected $listeners = ['deleteItem' => 'deleteItem', 'refreshTable' => '$refresh'];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->activeRoleIs('SUPER ADMIN', 'HR', 'hr', 'HR-SYR', 'HR-ALP'),
            403
        );

        $this->isCreatePage = request()->boolean('create');

        if ($this->isCreatePage) {
            $this->resetCreateForm();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        redirect()->to(route('course-categories.index', ['create' => 1]));
    }

    public function edit(int $id): void
    {
        $cat = CourseCategory::findOrFail($id);
        $this->editingId = $cat->id;
        $this->name_en = (string) ($cat->name_en ?? '');
        $this->name_ar = (string) ($cat->name_ar ?? '');

        redirect()->to(route('course-categories.index', ['create' => 1, 'edit' => $id]));
    }

    public function save(): void
    {
        $this->validate();

        if (request()->filled('edit')) {
            $id = (int) request()->query('edit');
            CourseCategory::whereKey($id)->update([
                'name_en' => $this->name_en,
                'name_ar' => $this->name_ar,
            ]);
        } elseif ($this->editingId) {
            CourseCategory::whereKey($this->editingId)->update([
                'name_en' => $this->name_en,
                'name_ar' => $this->name_ar,
            ]);
        } else {
            CourseCategory::create([
                'name_en' => $this->name_en,
                'name_ar' => $this->name_ar,
            ]);
        }

        $this->dispatch('notify', type: 'success', message: __('Saved successfully'));

        redirect()->to(route('course-categories.index'));
    }

    public function backToIndex(): void
    {
        redirect()->to(route('course-categories.index'));
    }

    public function confirmDelete(int $id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('deleteItem', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function deleteItem(array $payload): void
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

    protected function resetCreateForm(): void
    {
        if (request()->filled('edit')) {
            $id = (int) request()->query('edit');
            $cat = CourseCategory::findOrFail($id);
            $this->editingId = $cat->id;
            $this->name_en = (string) ($cat->name_en ?? '');
            $this->name_ar = (string) ($cat->name_ar ?? '');
        } else {
            $this->editingId = null;
            $this->name_en = '';
            $this->name_ar = '';
        }
    }

    public function render(): View
    {
        return view('livewire.basics.course-category-module', [
            'categories' => $this->categories,
        ])->title(__('Course Categories'));
    }
}
