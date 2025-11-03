<?php

namespace App\Livewire\News;

use Livewire\Component;
use App\Models\News;

class NewsModule extends Component
{
    protected $listeners = ['openNewsEdit' => 'edit'];

    public bool $showModal = false;
    public string $modalTitle = '';

    public array $item = [
        'id' => null,
        'title' => '',
        'description' => '',
        'category' => '',
        'photo' => '',
        'display_duration' => '1_day',
        'published_at' => '',
    ];

    protected function rules()
    {
        return [
            'item.title' => ['required', 'string', 'max:255'],
            'item.description' => ['nullable', 'string'],
            'item.category' => ['nullable', 'string', 'max:255'],
            'item.photo' => ['nullable', 'string'],
            'item.display_duration' => ['required', 'string'],
        ];
    }

    public function render()
    {
        return view('livewire.news.news-module');
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->item = [
            'id' => null,
            'title' => '',
            'description' => '',
            'category' => '',
            'photo' => '',
            'display_duration' => '1_day',
            'published_at' => '',
        ];
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $news = News::findOrFail($id);
        $this->item = $news->toArray();
        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $news = !empty($this->item['id'])
            ? News::findOrFail($this->item['id'])
            : new News();

        $news->fill($this->item);
        $news->published_at = now();
        $news->save();

        $this->dispatch('refreshNewsTable');
        $this->showModal = false;

        session()->flash('success', __('general.success-message'));
    }
}
