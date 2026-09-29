<?php

namespace App\Livewire\Recruitment\PublicSite;

use App\Models\City;
use App\Models\PublicRecruitmentLink;
use App\Services\RecruitmentApplicationFormService;
use App\Services\RecruitmentApplicationSubmissionService;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * TEMPORARY Team 2 test form at `/careers-test/{token}` (non-production only;
 * Team 1's `/careers/{token}` wizard is the real applicant form). It shows the
 * published job post and takes
 * the application form submission (recruitment-execution-plan-ar.md, Phase 1
 * Task 15 + Phase 2 Task 1).
 *
 * Unauthenticated by design, same shape as ShowDigitalId: the token is looked
 * up by its hash, a bad or stale one 404s exactly like one that never
 * existed, and nothing here trusts anything the browser had cached — the
 * service re-checks campaign and link state again under a lock at submit
 * time, because this page can sit open in a tab for a long time.
 */
#[Layout('components.layouts.public')]
class Apply extends Component
{
    use WithFileUploads;

    public PublicRecruitmentLink $link;

    public string $full_name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?int $city_id = null;

    public bool $privacy_accepted = false;

    /** @var array<string, mixed> */
    public array $answers = [];

    /** @var array<string, mixed> File-field uploads, keyed by field key like the answers. */
    public array $uploads = [];

    public bool $submitted = false;

    public function mount(string $token): void
    {
        // A locale switch link (?lang=ar|en) is enough for a public page that
        // has no session of its own; the full locale-routing machinery is for
        // the authenticated app.
        if (in_array(request('lang'), ['en', 'ar'], true)) {
            App::setLocale(request('lang'));
        }

        $link = PublicRecruitmentLink::query()
            ->where('token_hash', hash('sha256', $token))
            ->with(['campaign', 'jobPostVersion', 'formVersion'])
            ->first();

        // Same 404 whether the token never existed, expired, was disabled, or
        // its campaign stopped accepting submissions — Phase 1 Task 15 is
        // explicit that a bad token must not be distinguishable from any
        // other reason the page isn't there.
        abort_unless(
            $link !== null
                && $link->status === 'active'
                && ! $link->expires_at?->isPast()
                && app(RecruitmentApplicationSubmissionService::class)->isAcceptingSubmissions($link->campaign),
            404,
        );

        $this->link = $link;

        // select2-input binds through @entangle, which fails outright
        // ("property cannot be found") for a key that doesn't exist yet — so
        // every answer key is created up front, and the selects actually bind.
        foreach ($this->fields() as $field) {
            if ($field['type'] !== 'file') {
                $this->answers[$field['key']] = $field['type'] === 'checkbox' ? false : null;
            }
        }
    }

    public function submit(RecruitmentApplicationSubmissionService $service): void
    {
        // Phone keyboards and autofill routinely add a trailing space, which
        // email:rfc rejects — the HTTP TrimStrings middleware never sees
        // Livewire property updates.
        $this->full_name = trim($this->full_name);
        $this->email = trim($this->email);
        $this->phone = $this->phone === null ? null : trim($this->phone);

        $this->validate([
            'full_name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email:rfc', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'privacy_accepted' => ['accepted'],
            ...$this->uploadRules(),
        ], [
            'privacy_accepted.accepted' => __('recruitment-applicants.public_apply_privacy_required'),
        ], [
            'full_name' => __('recruitment-applicants.public_apply_full_name'),
            'email' => __('recruitment-applicants.public_apply_email'),
            'phone' => __('recruitment-applicants.public_apply_phone'),
            ...$this->uploadAttributes(),
        ]);

        $service->submit($this->link, [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'city_id' => $this->city_id ?: null,
            'privacy_notice_version' => $this->link->formVersion?->privacy_notice_version,
            'privacy_accepted_at' => true,
            'consent_payload_json' => [
                'accepted_at' => now()->toIso8601String(),
                'response_copy' => (bool) ($this->answers['response_copy'] ?? false),
                'locale' => app()->getLocale(),
            ],
            'answers' => $this->answers,
            'files' => $this->uploads,
        ], [
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);

        // No confirmation email is queued from here yet — Phase 1 Task 11's
        // notification layer covers the internal side (next approver, HR);
        // an applicant-facing "we received it" email is Phase 2 scope not
        // built here.
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.recruitment.public-site.apply', [
            'jobPostVersion' => $this->link->jobPostVersion,
            'fields' => $this->fields(),
            'cities' => City::query()->where('is_active', true)->orderBy(transColumn('name'))->get(),
        ]);
    }

    /**
     * Team 1's reader, so the page shows exactly the form their builder
     * published. The identity fields are the static inputs above and are not
     * repeated.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fields(): array
    {
        return array_values(array_filter(
            app(RecruitmentApplicationFormService::class)
                ->normalizeFields((array) ($this->link->formVersion?->fields_json ?? [])),
            fn (array $field) => ! in_array($field['key'], ['full_name', 'email', 'phone', 'privacy_consent'], true),
        ));
    }

    /**
     * Type and size limits for each file field, matching Team 1's
     * validateSubmission() (their field's `accept` list, 10 MB, max_files).
     * Presence is not checked here: the submission service reports a missing
     * required file under answers.{key}, next to the field.
     *
     * @return array<string, array<int, string>>
     */
    private function uploadRules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            if ($field['type'] !== 'file') {
                continue;
            }

            $fileRules = ['file', 'mimes:'.implode(',', $field['accept']), 'max:10240'];

            if ($field['multiple']) {
                $rules['uploads.'.$field['key']] = ['nullable', 'array', 'max:'.$field['max_files']];
                $rules['uploads.'.$field['key'].'.*'] = $fileRules;
            } else {
                $rules['uploads.'.$field['key']] = ['nullable', ...$fileRules];
            }
        }

        return $rules;
    }

    /**
     * The field's own label in validation messages instead of "uploads.cv".
     *
     * @return array<string, string>
     */
    private function uploadAttributes(): array
    {
        $locale = app()->getLocale();

        return collect($this->fields())
            ->where('type', 'file')
            ->flatMap(function (array $field) use ($locale) {
                $label = $field['label'][$locale] ?: ($field['label']['en'] ?: $field['key']);

                return ['uploads.'.$field['key'] => $label, 'uploads.'.$field['key'].'.*' => $label];
            })
            ->all();
    }
}
