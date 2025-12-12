<?php

namespace App\Livewire\Basics;

use App\Models\CourseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class CourseCategoryModule extends Component
{
    use WithPagination, SortableTable;

    public string $search = '';

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

    protected $listeners = ['delete' => 'delete'];

    public function mount(): void
    {
        $this->resetForm();
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
        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();
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
            LivewireAlert::title(__('general.error'))->text(__('Cannot delete: category has courses.'))->error()->toast()->position('top-start')->show();
            return;
        }

        $cat->delete();

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();
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
        $items = CourseCategory::query()
            ->withCount('courses')
            ->when($this->search, function (Builder $q) {
                $q->where(function (Builder $qq) {
                    $qq->where('name_en', 'like', "%{$this->search}%")
                        ->orWhere('name_ar', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.basics.course-category-module', compact('items'));
    }
}
