<?php

namespace App\Livewire\News;

use Livewire\Component;
use App\Models\News;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Models\City;
use App\Models\Center;
use App\Models\Program;

class NewsModule extends Component
{
    use WithFileUploads;

    protected $listeners = ['openNewsEdit' => 'edit'];

    public bool $showModal = false;
    public string $modalTitle = '';
    public array $item = [
        'id' => null,
        'title' => '',
        'description' => '',
        'newsable_type' => '',
        'newsable_id' => null,
        'photo' => '',
        'display_duration' => '1_day',
        'published_at' => '',
    ];

    protected function rules()
    {
        return [
            'item.title' => ['required', 'string', 'max:255'],
            'item.description' => ['nullable', 'string'],
            'item.newsable_type' => ['nullable', 'string', Rule::in(['App\\Models\\City', 'App\\Models\\Center', 'App\\Models\\Program'])],
            'item.newsable_id' => [Rule::requiredIf(!empty($this->item['newsable_type'])), 'nullable', 'integer'],
            'item.display_duration' => ['required', 'string'],
            'item.photo' => ['nullable', Rule::when($this->item['photo'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile, ['image', 'max:2048'])],
        ];
    }

    public function getCitiesProperty()
    {
        return City::where('is_active', true)->get(['id', 'name_en', 'name_ar']);
    }

    public function getCentersProperty()
    {
        return Center::where('is_active', true)->get(['id', 'name']);
    }

    public function getProgramsProperty()
    {
        return Program::get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.news.news-module', [
            'cities' => $this->cities,
            'centers' => $this->centers,
            'programs' => $this->programs,
        ]);
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->item = [
            'id' => null,
            'title' => '',
            'description' => '',
            'newsable_type' => '',
            'newsable_id' => null,
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
        $this->item['newsable_type'] = $news->newsable_type ?? '';
        $this->item['newsable_id'] = $news->newsable_id;

        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $news = !empty($this->item['id']) ? News::findOrFail($this->item['id']) : new News();
        $photoPath = $news->photo;

        if ($this->item['photo'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            if ($news->photo) {
                Storage::disk('public')->delete($news->photo);
            }
            $photoPath = $this->item['photo']->store('news', 'public');
        } elseif (empty($this->item['photo']) && $news->photo) {
            Storage::disk('public')->delete($news->photo);
            $photoPath = null;
        }

        $dataToFill = $this->item;
        unset($dataToFill['photo']);

        if (empty($dataToFill['newsable_type']) || empty($dataToFill['newsable_id'])) {
            $dataToFill['newsable_type'] = null;
            $dataToFill['newsable_id'] = null;
        }

        $news->fill($dataToFill);
        $news->photo = $photoPath;
        $news->published_at = now();
        $news->save();

        $this->dispatch('refreshNewsTable');
        $this->showModal = false;
        session()->flash('success', __('general.success-message'));
    }

    public function updatedItemNewsableType($value)
    {
        $this->item['newsable_id'] = null;
    }
}
