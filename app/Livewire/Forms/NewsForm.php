<?php

namespace App\Livewire\Forms;

use App\Models\Center;
use App\Models\City;
use App\Models\Employee;
use App\Models\News;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class NewsForm extends Form
{
    public string $title = '';

    public string $description = '';

    public mixed $photo = null;

    public ?string $display_until = null;

    public ?string $event_date = null;

    public string $newsable_type = '';

    public string|int|null $newsable_id = '';

    /** @return list<class-string> */
    public static function allowedNewsableTypes(): array
    {
        return [
            City::class,
            Center::class,
            Program::class,
            Employee::class,
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'newsable_type' => ['nullable', 'string', Rule::in(self::allowedNewsableTypes())],
            'newsable_id' => [
                Rule::requiredIf(filled($this->newsable_type)),
                'nullable',
                'integer',
            ],
            'display_until' => ['nullable', 'date'],
            'event_date' => ['nullable', 'date'],
            'photo' => [
                'nullable',
                Rule::when(
                    $this->photo instanceof TemporaryUploadedFile,
                    ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
                ),
            ],
        ];
    }

    public function fillFromNews(News $news): void
    {
        $this->fill([
            'title' => $news->title,
            'description' => $news->description ?? '',
            'newsable_type' => $news->newsable_type ?? '',
            'newsable_id' => $news->newsable_id ?? '',
            'display_until' => $news->display_until?->format('Y-m-d'),
            'event_date' => $news->event_date?->format('Y-m-d'),
            'photo' => null,
        ]);
    }

    public function entities(): Collection
    {
        return match ($this->newsable_type) {
            City::class => City::select('id', 'name_en', 'name_ar')->where('is_active', true)->orderBy('name_en')->get(),
            Center::class => Center::select('id', transColumn('name'))->where('is_active', true)->orderBy(transColumn('name'))->get(),
            Program::class => Program::select('id', 'name')->orderBy('name')->get(),
            Employee::class => Employee::select('id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar')
                ->where('is_active', true)
                ->orderBy('first_name_en')
                ->get(),
            default => collect(),
        };
    }

    public function updatedNewsableType(): void
    {
        $this->newsable_id = '';
    }

    public function persist(?News $news = null): News
    {
        $this->validate();

        $isNew = $news === null;
        $news ??= new News;

        if ($isNew) {
            $news->published_at = now();
        }

        $news->fill([
            'title' => $this->title,
            'description' => $this->description,
            'newsable_type' => $this->newsable_type ?: null,
            'newsable_id' => $this->newsable_id ?: null,
            'display_until' => $this->display_until ? Carbon::parse($this->display_until) : null,
            'event_date' => $this->event_date ? Carbon::parse($this->event_date) : null,
        ]);

        $news->save();

        if ($this->photo instanceof TemporaryUploadedFile) {
            $news->clearMediaCollection('news-image');
            $news->addMedia($this->photo)->toMediaCollection('news-image');
        }

        return $news;
    }
}
