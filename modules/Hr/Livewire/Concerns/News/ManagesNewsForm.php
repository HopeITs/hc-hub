<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\News;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Enums\News\AudienceType;
use Modules\Hr\Enums\News\NewsState;
use Modules\Hr\Enums\News\NewsStatus;
use Modules\Hr\Enums\News\NewsType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\News;
use Throwable;

/**
 * The news form's state and rules (the old NewsForm): type, bilingual title
 * and text, tags, audience, when it goes out, the event's times and when it
 * stops showing. Arabic is required, English optional.
 *
 * Rules that go beyond field shapes:
 *  - an audience other than "everyone" needs at least one target, chosen
 *    among the active ones offered;
 *  - a scheduled publish time must be in the future for a new item, a draft
 *    or a scheduled one (an item already out may keep its past time);
 *  - an event needs a start, and its end cannot be before it;
 *  - "show until" must be after the publish time and not before the event.
 */
trait ManagesNewsForm
{
    public string $type = 'news';

    public string $titleAr = '';

    public string $titleEn = '';

    public string $descriptionAr = '';

    public string $descriptionEn = '';

    /**
     * Tags typed as one comma-separated line.
     */
    public string $tagLine = '';

    public string $audienceType = 'all';

    /**
     * @var list<string>
     */
    public array $audienceIds = [];

    public string $publishMode = 'now';

    public string $publishAt = '';

    public string $eventStartsAt = '';

    public string $eventEndsAt = '';

    public string $displayUntil = '';

    /**
     * @var TemporaryUploadedFile|null
     */
    public $photo = null;

    public function updatedAudienceType(): void
    {
        $this->audienceIds = [];
        $this->resetErrorBag(['audienceIds', 'audienceIds.*']);
    }

    public function updatedType(): void
    {
        if ($this->typeEnum() !== NewsType::Event) {
            $this->eventStartsAt = '';
            $this->eventEndsAt = '';
        }

        $this->resetErrorBag(['eventStartsAt', 'eventEndsAt']);
    }

    protected function typeEnum(): NewsType
    {
        return NewsType::tryFrom($this->type) ?? NewsType::News;
    }

    protected function audienceEnum(): AudienceType
    {
        return AudienceType::tryFrom($this->audienceType) ?? AudienceType::All;
    }

    protected function fillNewsForm(News $news): void
    {
        $scheduled = $news->published_at !== null && ($news->published_at->isFuture() || $news->status === NewsStatus::Draft);
        $moment = fn (?CarbonInterface $value): string => $value?->format('Y-m-d\TH:i') ?? '';

        $this->type = $news->type->value;
        $this->titleAr = (string) $news->title_ar;
        $this->titleEn = (string) $news->title_en;
        $this->descriptionAr = (string) $news->description_ar;
        $this->descriptionEn = (string) $news->description_en;
        $this->tagLine = implode(', ', $news->tagNames());
        $this->audienceType = $news->audience_type->value;
        $this->audienceIds = $news->audienceTargets()->pluck('target_id')->map(fn (mixed $id): string => (string) $id)->all();
        $this->publishMode = $scheduled ? 'schedule' : 'now';
        $this->publishAt = $scheduled ? $moment($news->published_at) : '';
        $this->eventStartsAt = $moment($news->event_starts_at);
        $this->eventEndsAt = $moment($news->event_ends_at);
        $this->displayUntil = $moment($news->display_until);
    }

    /**
     * The targets offered for the chosen audience: id => name.
     *
     * @return array<int, string>
     */
    protected function audienceOptions(?AudienceType $audience = null): array
    {
        return match (($audience ?? $this->audienceEnum())->modelClass()) {
            City::class => City::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_ar'])->mapWithKeys(fn (City $city): array => [(int) $city->id => (string) $city->name])->all(),
            Center::class => Center::query()->where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_ar'])->mapWithKeys(fn (Center $center): array => [(int) $center->id => (string) $center->name])->all(),
            Program::class => Program::query()->orderBy('name')->pluck('name', 'id')->map(fn (mixed $name): string => trim((string) $name))->all(),
            Department::class => Department::query()->orderBy('name')->pluck('name', 'id')->map(fn (mixed $name): string => trim((string) $name))->all(),
            Employee::class => Employee::query()->select(Employee::NameColumns)->where('is_active', true)->orderBy('first_name_en')->get()->mapWithKeys(fn (Employee $employee): array => [(int) $employee->id => (string) $employee->name])->all(),
            default => [],
        };
    }

    /**
     * Validate the form and return the values SaveNews takes.
     *
     * @return array{type: NewsType, title_ar: string, title_en: string|null, description_ar: string, description_en: string|null, tags: list<string>, audience_type: AudienceType, audience_ids: list<int>, publish_at: CarbonInterface|null, event_starts_at: CarbonInterface|null, event_ends_at: CarbonInterface|null, display_until: CarbonInterface|null}
     */
    protected function validatedNews(?News $news): array
    {
        $audience = AudienceType::tryFrom($this->audienceType);
        $isEvent = $this->typeEnum() === NewsType::Event;
        $scheduled = $this->publishMode === 'schedule';
        $mustBeFuture = $news === null || in_array($news->state(), [NewsState::Draft, NewsState::Scheduled], true);
        $tags = News::normalizeTags(explode(',', $this->tagLine));

        if (! $isEvent) {
            $this->eventStartsAt = '';
            $this->eventEndsAt = '';
        }

        if (! $audience?->needsTargets()) {
            $this->audienceIds = [];
        }

        $this->validate([
            'type' => ['required', Rule::enum(NewsType::class)],
            'titleAr' => ['required', 'string', 'max:255'],
            'titleEn' => ['nullable', 'string', 'max:255'],
            'descriptionAr' => ['required', 'string', 'max:20000'],
            'descriptionEn' => ['nullable', 'string', 'max:20000'],
            'tagLine' => ['nullable', 'string', 'max:'.(News::MAX_TAGS * (News::MAX_TAG_LENGTH + 2))],
            'audienceType' => ['required', Rule::enum(AudienceType::class)],
            'audienceIds' => $audience?->needsTargets() ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'audienceIds.*' => $audience?->needsTargets() ? ['integer', Rule::in(array_keys($this->audienceOptions($audience)))] : ['nullable'],
            'publishMode' => ['required', Rule::in(['now', 'schedule'])],
            'publishAt' => $scheduled ? array_filter(['required', 'date', $mustBeFuture ? 'after:now' : null]) : ['nullable'],
            'eventStartsAt' => $isEvent ? ['required', 'date'] : ['nullable'],
            'eventEndsAt' => $isEvent ? ['nullable', 'date', 'after_or_equal:eventStartsAt'] : ['nullable'],
            'displayUntil' => ['nullable', 'date', $this->displayUntilRule($news)],
            'photo' => ['nullable', Rule::when($this->photo instanceof TemporaryUploadedFile, ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'])],
        ], [
            'audienceIds.required' => __('hr_news.form.errors.audience_required'),
            'audienceIds.min' => __('hr_news.form.errors.audience_required'),
            'audienceIds.*.in' => __('hr_news.form.errors.audience_offered'),
            'publishAt.required' => __('hr_news.form.errors.publish_at_required'),
            'publishAt.after' => __('hr_news.form.errors.publish_at_future'),
            'eventStartsAt.required' => __('hr_news.form.errors.event_start_required'),
            'eventEndsAt.after_or_equal' => __('hr_news.form.errors.event_end_after_start'),
        ], [
            'type' => __('hr_news.form.fields.type'),
            'titleAr' => __('hr_news.form.fields.title_ar'),
            'titleEn' => __('hr_news.form.fields.title_en'),
            'descriptionAr' => __('hr_news.form.fields.description_ar'),
            'descriptionEn' => __('hr_news.form.fields.description_en'),
            'tagLine' => __('hr_news.form.fields.tags'),
            'audienceType' => __('hr_news.form.fields.audience_type'),
            'audienceIds' => __('hr_news.form.fields.audience_ids'),
            'publishAt' => __('hr_news.form.fields.publish_at'),
            'eventStartsAt' => __('hr_news.form.fields.event_starts_at'),
            'eventEndsAt' => __('hr_news.form.fields.event_ends_at'),
            'displayUntil' => __('hr_news.form.fields.display_until'),
            'photo' => __('hr_news.form.fields.photo'),
        ]);

        return [
            'type' => $this->typeEnum(),
            'title_ar' => trim($this->titleAr),
            'title_en' => trim($this->titleEn) ?: null,
            'description_ar' => trim($this->descriptionAr),
            'description_en' => trim($this->descriptionEn) ?: null,
            'tags' => $tags,
            'audience_type' => $this->audienceEnum(),
            'audience_ids' => array_values(array_unique(array_map('intval', $this->audienceIds))),
            'publish_at' => $scheduled ? $this->parseMoment($this->publishAt) : null,
            'event_starts_at' => $isEvent ? $this->parseMoment($this->eventStartsAt) : null,
            'event_ends_at' => $isEvent ? $this->parseMoment($this->eventEndsAt) : null,
            'display_until' => $this->parseMoment($this->displayUntil),
        ];
    }

    protected function parseMoment(string $value): ?CarbonInterface
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value, config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * "Show until" must leave the item a moment on screen: after it goes
     * out, and not before its event starts.
     */
    private function displayUntilRule(?News $news): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($news): void {
            $until = $this->parseMoment((string) $value);

            if ($until === null) {
                return;
            }

            $alreadyOut = $news !== null && $news->status === NewsStatus::Published && $news->published_at !== null && $news->published_at->lte(now());
            $publishAt = $this->publishMode === 'schedule' ? $this->parseMoment($this->publishAt) : ($alreadyOut ? $news->published_at : now());

            if ($publishAt !== null && $until->lte($publishAt)) {
                $fail(__('hr_news.form.errors.display_until_after_publish'));

                return;
            }

            $start = $this->typeEnum() === NewsType::Event ? $this->parseMoment($this->eventStartsAt) : null;

            if ($start !== null && $until->lt($start)) {
                $fail(__('hr_news.form.errors.display_until_after_event'));
            }
        };
    }
}
