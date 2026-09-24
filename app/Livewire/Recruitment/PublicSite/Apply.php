<?php

namespace App\Livewire\Recruitment\PublicSite;

use App\Models\City;
use App\Models\PublicRecruitmentLink;
use App\Services\RecruitmentApplicationFormService;
use App\Services\RecruitmentApplicationSubmissionService;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Layout;
use Livewire\Component;

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
    public PublicRecruitmentLink $link;

    public string $full_name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?int $city_id = null;

    public bool $privacy_accepted = false;

    /** @var array<string, mixed> */
    public array $answers = [];

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
    }

    public function submit(RecruitmentApplicationSubmissionService $service): void
    {
        $this->validate([
            'full_name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email:rfc', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'privacy_accepted' => ['accepted'],
        ]);

        $service->submit($this->link, [
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'city_id' => $this->city_id ?: null,
            'privacy_notice_version' => $this->link->formVersion?->privacy_notice_version,
            'privacy_accepted_at' => true,
            'consent_payload_json' => ['accepted_at' => now()->toIso8601String()],
            'answers' => $this->answers,
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
            // Team 1's reader, so the page shows exactly the form their
            // builder published. The identity fields are the static inputs
            // above and are not repeated.
            'fields' => array_values(array_filter(
                app(RecruitmentApplicationFormService::class)
                    ->normalizeFields((array) ($this->link->formVersion?->fields_json ?? [])),
                fn (array $field) => ! in_array($field['key'], ['full_name', 'email', 'phone', 'privacy_consent'], true),
            )),
            'cities' => City::query()->where('is_active', true)->orderBy(transColumn('name'))->get(),
        ]);
    }
}
