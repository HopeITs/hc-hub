<?php

namespace App\Livewire\News;

use App\Models\News;
use App\Models\City;
use App\Models\Center;
use App\Models\Program;
use App\Models\Employee;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class NewsCreate extends Component
{
    use WithFileUploads;

    public ?News $news = null;

    public array $item = [
        'title' => '',
        'description' => '',
        'photo' => null,
        'display_until' => null,
        'event_date' => null,
        'newsable_type' => '',
        'newsable_id' => '',
    ];

    protected function rules()
    {
        return [
            'item.title' => 'required|string|max:255',
            'item.description' => 'required|string',
            'item.newsable_type' => 'nullable|string',
            'item.newsable_id' => 'nullable|integer',
            'item.display_until' => 'nullable|date',
            'item.event_date' => 'nullable|date',
            'item.photo' => [
                'nullable',
                Rule::when(
                    $this->item['photo'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile,
                    ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048']
                ),
            ],
        ];
    }

    public function mount(News $news = null): void
    {
        if ($news) {
            $this->news = $news;
            $this->item = [
                'title' => $news->title,
                'description' => $news->description,
                'newsable_type' => $news->newsable_type ?? '',
                'newsable_id' => $news->newsable_id ?? '',
                'photo' => '',
                'display_until' => optional($news->display_until)?->format('Y-m-d'),
                'event_date' => optional($news->event_date)?->format('Y-m-d'),
            ];
        }
    }

    public function getEntitiesProperty()
    {
        return match ($this->item['newsable_type'] ?? '') {
            City::class => City::all(),
            Center::class => Center::select('id', 'name')->get(),
            Program::class => Program::select('id', 'name')->get(),
            Employee::class => Employee::all(),
            default => collect(),
        };
    }

    public function updatedItemNewsableType($value): void
    {
        $this->item['newsable_id'] = '';
    }

    public function save()
    {
        $this->validate();

        $isEdit = $this->news !== null;

        if (!$this->news) {
            $this->news = new News();
            $this->news->published_at = now();
        }

        $this->news->fill([
            'title' => $this->item['title'],
            'description' => $this->item['description'],
            'newsable_type' => $this->item['newsable_type'] ?: null,
            'newsable_id' => $this->item['newsable_id'] ?: null,
            'display_until' => $this->item['display_until'] ? Carbon::parse($this->item['display_until']) : null,
            'event_date' => $this->item['event_date'] ? Carbon::parse($this->item['event_date']) : null,
        ]);

        $this->news->save();

        if ($this->item['photo'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $this->news->clearMediaCollection('news-image');
            $this->news->addMedia($this->item['photo'])->toMediaCollection('news-image');
        }

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();

        return to_route('news.index');
    }

    public function render()
    {
        return view('livewire.news.news-create', [
            'cities' => City::select('id')->get(),
            'centers' => Center::select('id', 'name')->get(),
            'programs' => Program::select('id', 'name')->get(),
            'employees' => Employee::select('id')->get(),
        ]);
    }
}
