<?php

namespace Tests\Feature\Recruitment;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentCampaignLinkMode;
use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType;
use App\Livewire\Recruitment\PublicLinks\Manager;
use App\Models\ApplicationForm;
use App\Models\Department;
use App\Models\HiringCampaign;
use App\Models\JobLevel;
use App\Models\JobPost;
use App\Models\Position;
use App\Models\Program;
use App\Models\PublicRecruitmentLink;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentRequestVersion;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\HiringCampaignService;
use App\Services\PublicRecruitmentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class PublicRecruitmentLinkTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('recruitment-quarantine');
        Storage::fake('recruitment-files');
        config([
            'recruitment-uploads.quarantine_disk' => 'recruitment-quarantine',
            'recruitment-uploads.clean_disk' => 'recruitment-files',
        ]);
    }

    public function test_hr_generates_a_hashed_frozen_link_and_regeneration_disables_the_old_link(): void
    {
        [$campaign, $hr, $request] = $this->makePublishedCampaign();
        $service = app(PublicRecruitmentLinkService::class);

        $first = $service->generate($campaign, $hr, now()->addDays(10), RecruitmentCampaignLinkMode::PublicOpen);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first['token']);
        $this->assertSame(hash('sha256', $first['token']), $first['link']->token_hash);
        $this->assertStringNotContainsString($first['token'], json_encode($first['link']));
        $this->assertSame(route('careers.show', $first['token']), $first['url']);

        $second = $service->generate($campaign, $hr, now()->addDays(8), RecruitmentCampaignLinkMode::PublicOpen);

        $this->assertSame(PublicRecruitmentLink::STATUS_DISABLED, $first['link']->fresh()->status);
        $this->assertSame(PublicRecruitmentLink::STATUS_ACTIVE, $second['link']->fresh()->status);
        $this->assertSame(1, $campaign->publicLinks()->where('status', PublicRecruitmentLink::STATUS_ACTIVE)->count());
        $this->assertDatabaseHas('recruitment_request_events', [
            'recruitment_request_id' => $request->id,
            'event_type' => RecruitmentEventType::PublicLinkChanged,
            'actor_user_id' => $hr->id,
        ]);
    }

    public function test_generating_the_first_link_keeps_a_valid_draft_campaign_private(): void
    {
        [$campaign, $hr, $request] = $this->makePublishedCampaign();
        $campaign->forceFill(['status' => HiringCampaignStatus::Draft])->save();
        $component = Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign])
            ->set('customExpiry', true)
            ->set('expiresAt', now()->addDays(5)->format('Y-m-d\TH:i'))
            ->call('generate')
            ->assertSet('notice', __('recruitment.public_link.generated'))
            ->assertSet('generatedUrl', fn ($url) => str_contains($url, '/careers/'));

        $this->assertSame(HiringCampaignStatus::Draft, $campaign->fresh()->status);

        $link = $campaign->publicLinks()->where('status', PublicRecruitmentLink::STATUS_ACTIVE)->firstOrFail();
        $token = basename((string) parse_url($component->get('generatedUrl'), PHP_URL_PATH));
        $this->get(route('careers.show', ['token' => $token]))->assertNotFound();
        $hr->roles()->wherePivot('is_active', true)->firstOrFail()
            ->givePermissionTo(Permission::findOrCreate('recruitment-campaigns.manage', 'web'));
        app(HiringCampaignService::class)->publish($hr, $campaign->fresh());
        $this->get(route('careers.show', ['token' => $token]))->assertOk();
        $this->assertSame(PublicRecruitmentLink::STATUS_ACTIVE, $link->status);
    }

    public function test_link_permission_can_prepare_a_link_without_publishing_a_draft_campaign(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $campaign->forceFill(['status' => HiringCampaignStatus::Draft])->save();

        Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign])
            ->set('customExpiry', true)
            ->set('expiresAt', now()->addDays(5)->format('Y-m-d\TH:i'))
            ->call('generate')
            ->assertSet('notice', __('recruitment.public_link.generated'));

        $this->assertSame(HiringCampaignStatus::Draft, $campaign->fresh()->status);
        $this->assertSame(1, $campaign->publicLinks()->count());
    }

    public function test_public_page_uses_the_frozen_versions_and_exposes_no_internal_ids(): void
    {
        [$campaign, $hr, $request, $jobPost, $form] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );

        $newJobVersion = $jobPost->versions()->create([
            'version_no' => 2,
            'title_en' => 'New internal title',
            'title_ar' => 'عنوان داخلي جديد',
            'body_en' => 'New content',
            'body_ar' => 'محتوى جديد',
            'public_payload_json' => ['city' => 'DAMASCUS', 'program' => 'OTHER', 'job_title' => 'Changed'],
            'status' => 'published',
            'published_at' => now(),
        ]);
        $jobPost->forceFill(['current_version_id' => $newJobVersion->id])->save();
        $newFormVersion = $form->versions()->create([
            'version_no' => 2,
            'fields_json' => [['key' => 'email']],
            'privacy_notice_version' => 'v2',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $form->forceFill(['current_version_id' => $newFormVersion->id])->save();

        $response = $this->get(route('careers.show', $result['token']));

        $response->assertOk()
            ->assertSee("We're Hiring — IT Specialist")
            ->assertDontSee('New internal title')
            ->assertDontSee($request->request_number)
            ->assertDontSee('campaign_id')
            ->assertDontSee('position_id')
            ->assertDontSee('requester_user_id');
    }

    public function test_public_form_renders_as_a_validated_section_wizard(): void
    {
        [$campaign, $hr, , , $form] = $this->makePublishedCampaign();
        $wizardVersion = $form->versions()->create([
            'version_no' => 2,
            'fields_json' => [
                ['key' => 'vacancy_city'],
                ['key' => 'full_name'],
                ['key' => 'education_level'],
                ['key' => 'cv'],
                ['key' => 'privacy_consent'],
            ],
            'privacy_notice_version' => 'v2',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $form->forceFill(['current_version_id' => $wizardVersion->id])->save();

        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );

        $this->get(route('careers.show', $result['token']))
            ->assertOk()
            ->assertSee('data-recruitment-wizard', false)
            ->assertSee('data-wizard-step', false)
            ->assertSee('data-wizard-next', false)
            ->assertSee('name="answers[full_name]"', false)
            ->assertSee('name="answers[cv]"', false)
            ->assertSee('name="submission_key"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('required', false)
            ->assertSee(__('careers.add_file'))
            ->assertDontSee('Secure file uploads will be enabled');
    }

    public function test_invalid_disabled_expired_and_draft_campaign_links_share_a_generic_response(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $service = app(PublicRecruitmentLinkService::class);
        $result = $service->generate($campaign, $hr, now()->addDays(10), RecruitmentCampaignLinkMode::PublicOpen);

        $this->get('/careers/'.str_repeat('f', 64))
            ->assertNotFound()
            ->assertSee(__('careers.unavailable_title'));

        $service->disable($result['link'], $hr);
        $this->get(route('careers.show', $result['token']))
            ->assertNotFound()
            ->assertSee(__('careers.unavailable_title'));

        $expired = $service->generate($campaign, $hr, now()->addDays(10), RecruitmentCampaignLinkMode::PublicOpen);
        $expired['link']->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->get(route('careers.show', $expired['token']))
            ->assertNotFound()
            ->assertSee(__('careers.unavailable_title'));

        $draft = $service->generate($campaign, $hr, now()->addDays(10), RecruitmentCampaignLinkMode::PublicOpen);
        $campaign->forceFill(['status' => HiringCampaignStatus::Draft])->save();
        $this->get(route('careers.show', $draft['token']))
            ->assertNotFound()
            ->assertSee(__('careers.unavailable_title'));
    }

    public function test_public_submission_creates_records_and_rejects_internal_fields(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );

        $safe = $this->validSubmission('applicant@example.com', 'Applicant Name');

        $this->post(route('careers.submit', $result['token']), $safe)
            ->assertStatus(202)
            ->assertSee(__('careers.submitted_title'));
        $this->assertSame(1, RecruitmentApplicant::query()->count());
        $this->assertSame(1, RecruitmentApplication::query()->count());
        $this->assertDatabaseHas('recruitment_application_answers', ['field_key' => 'full_name']);
        $this->assertDatabaseHas('recruitment_applicant_files', ['file_type' => 'cv']);

        foreach ([
            ['invalid_request', [...$this->validSubmission('other1@example.com'), 'campaign_id' => $campaign->id]],
            ['invalid_request', [...$this->validSubmission('other2@example.com'), 'status' => 'selected']],
            ['validation', [...$this->validSubmission('other3@example.com'), 'answers' => [...$this->validSubmission('other3@example.com')['answers'], 'score' => 100]]],
            ['invalid_request', [...$this->validSubmission('other4@example.com'), 'website' => 'https://bot.example']],
        ] as [$reason, $payload]) {
            $this->post(route('careers.submit', $result['token']), $payload)
                ->assertUnprocessable()
                ->assertSee(__('careers.errors.'.$reason));
        }

        $this->assertSame(1, RecruitmentApplication::query()->count());
    }

    public function test_an_empty_optional_multi_file_field_does_not_reject_the_application(): void
    {
        [$campaign, $hr, , , $form] = $this->makePublishedCampaign();
        $form->currentVersion->forceFill(['fields_json' => [
            ['key' => 'full_name'],
            ['key' => 'email'],
            ['key' => 'recommendations'],
        ]])->save();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );

        // A browser posts an untouched `answers[recommendations][]` input as
        // one empty file part, which reaches the request as [null].
        $payload = $this->validSubmission('applicant@example.com');
        $payload['answers']['recommendations'] = [null];

        $this->post(route('careers.submit', $result['token']), $payload)->assertStatus(202);
        $this->assertDatabaseCount('recruitment_applicant_files', 1);
    }

    public function test_a_rejected_application_says_which_field_failed(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );
        $payload = $this->validSubmission('not-an-email');
        unset($payload['answers']['privacy_consent']);

        $this->post(route('careers.submit', $result['token']), $payload)
            ->assertUnprocessable()
            ->assertSee(__('careers.errors.validation'))
            ->assertSee('Email address')
            ->assertSee('I consent to the collection')
            ->assertDontSee('answers.email');

        $this->assertSame(0, RecruitmentApplication::query()->count());
    }

    public function test_language_switch_after_a_rejected_submission_returns_to_the_public_page(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );
        $payload = $this->validSubmission('not-an-email');

        $response = $this->post(route('careers.submit', $result['token']), $payload);

        $arabicPage = route('careers.show', ['token' => $result['token'], 'lang' => 'ar']);
        $wrongPostUrl = route('careers.submit', ['token' => $result['token'], 'lang' => 'ar']);
        $response->assertUnprocessable()
            ->assertSee('href="'.$arabicPage.'"', false)
            ->assertDontSee('href="'.$wrongPostUrl.'"', false);
        $this->get($arabicPage)
            ->assertOk()
            ->assertSee('dir="rtl"', false);
    }

    public function test_same_email_cannot_submit_twice_with_a_different_idempotency_key(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );

        $this->post(
            route('careers.submit', $result['token']),
            $this->validSubmission('applicant@example.com'),
        )->assertStatus(202);
        $this->post(
            route('careers.submit', $result['token']),
            $this->validSubmission(' APPLICANT@example.com ', 'Duplicate Applicant'),
        )->assertUnprocessable()->assertSee(__('careers.errors.generic'));

        $this->assertDatabaseCount('recruitment_applicants', 1);
        $this->assertDatabaseCount('recruitment_applications', 1);
    }

    public function test_failed_secure_upload_rolls_back_the_complete_application(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );
        config(['recruitment-uploads.quarantine_disk' => 'missing-recruitment-disk']);

        $this->post(
            route('careers.submit', $result['token']),
            $this->validSubmission('applicant@example.com'),
        )->assertUnprocessable()->assertSee(__('careers.errors.server_error'));

        $this->assertDatabaseCount('recruitment_applicants', 0);
        $this->assertDatabaseCount('recruitment_applications', 0);
        $this->assertDatabaseCount('recruitment_application_answers', 0);
        $this->assertDatabaseCount('recruitment_applicant_files', 0);
    }

    public function test_unknown_submission_tokens_use_the_same_generic_error_boundary(): void
    {
        $this->post('/careers/'.str_repeat('f', 64).'/applications', [
            'locale' => 'en',
            'submission_key' => bin2hex(random_bytes(32)),
            'answers' => [],
        ])->assertUnprocessable()->assertSee(__('careers.errors.link_expired'));
    }

    public function test_invitation_only_mode_normalizes_email_without_exposing_the_invite_list(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $service = app(PublicRecruitmentLinkService::class);
        $result = $service->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::InvitationOnly,
        );
        $invite = $service->addInvite($campaign->fresh(), $hr, 'Invited Person', 'Invited@Example.COM');
        $sameInvite = $service->addInvite($campaign->fresh(), $hr, 'Updated Name', ' invited@example.com ');

        $this->assertSame('invited@example.com', $invite->email_normalized);
        $this->assertSame($invite->id, $sameInvite->id);
        $this->assertSame(1, $campaign->invites()->count());
        $this->get(route('careers.show', $result['token']))
            ->assertOk()
            ->assertSee(__('careers.invitation_only'))
            ->assertDontSee('invited@example.com')
            ->assertDontSee('Invited Person');

        $this->post(
            route('careers.submit', $result['token']),
            $this->validSubmission('  INVITED@example.com ', 'Invited Person'),
        )->assertStatus(202);

        $this->post(
            route('careers.submit', $result['token']),
            $this->validSubmission('unknown@example.com', 'Unknown'),
        )->assertUnprocessable()->assertSee(__('careers.errors.generic'));

        $this->assertSame(1, RecruitmentApplication::query()->count());
        $this->assertSame('used', $invite->fresh()->status);
        $this->assertSame($invite->id, RecruitmentApplication::query()->sole()->campaign_invite_id);
    }

    public function test_closed_campaign_blocks_the_submission_boundary(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );
        $campaign->forceFill(['status' => HiringCampaignStatus::Closed])->save();

        $this->post(
            route('careers.submit', $result['token']),
            $this->validSubmission('applicant@example.com'),
        )->assertUnprocessable()->assertSee(__('careers.errors.campaign_closed'));

        $this->assertSame(0, RecruitmentApplication::query()->count());
    }

    public function test_public_submission_boundary_is_rate_limited_per_token_and_ip(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );
        $submissionKey = bin2hex(random_bytes(32));

        foreach (range(1, 5) as $attempt) {
            $this->post(
                route('careers.submit', $result['token']),
                $this->validSubmission('applicant@example.com', submissionKey: $submissionKey),
            )->assertStatus(202);
        }

        $this->post(
            route('careers.submit', $result['token']),
            $this->validSubmission('applicant@example.com', submissionKey: $submissionKey),
        )->assertTooManyRequests();
        $this->assertSame(1, RecruitmentApplication::query()->count());
    }

    public function test_scoped_hr_can_manage_the_link_and_invites_from_the_publishing_hub_component(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        // Who may apply is the campaign's setting; the link follows it.
        $campaign->forceFill(['link_mode' => RecruitmentCampaignLinkMode::InvitationOnly])->save();

        Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign])
            ->set('customExpiry', true)
            ->set('customExpiry', true)
            ->set('expiresAt', now()->addDays(5)->format('Y-m-d\TH:i'))
            ->assertSee('id="public-link-expiry" type="date"', false)
            ->assertDontSee('datetime-local', false)
            ->call('generate')
            ->assertSet('notice', __('recruitment.public_link.generated'))
            ->assertSet('generatedUrl', fn ($url) => str_contains($url, '/careers/'))
            ->set('inviteName', 'Candidate One')
            ->set('inviteEmail', 'candidate@example.com')
            ->call('addInvite')
            ->assertSee('candidate@example.com');

        $this->assertDatabaseHas('recruitment_campaign_invites', [
            'campaign_id' => $campaign->id,
            'email_normalized' => 'candidate@example.com',
            'status' => 'pending',
        ]);
    }

    /** The link takes the audience and the end date from the campaign itself. */
    public function test_a_generated_link_follows_the_campaign_audience_and_window(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $end = now()->addDays(9)->startOfMinute();
        $campaign->forceFill([
            'link_mode' => HiringCampaign::INVITE_ONLY,
            'ends_at' => $end,
        ])->save();

        Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign])
            ->call('generate')
            ->assertSet('generatedUrl', fn ($url) => str_contains($url, '/careers/'))
            ->assertSee(__('recruitment.public_link.invites'));

        $link = $campaign->publicLinks()->where('status', PublicRecruitmentLink::STATUS_ACTIVE)->sole();
        $this->assertTrue($link->expires_at->equalTo($end));
        $this->assertTrue($campaign->fresh()->isInvitationOnly());
    }

    /** Coming back to the page, the person who made the link can still copy it. */
    public function test_the_creator_can_copy_the_link_again_in_the_same_session(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();

        $url = Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign])
            ->call('generate')
            ->get('generatedUrl');

        Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign->fresh()])
            ->assertSee($url)
            ->assertDontSee(__('recruitment.public_link.hash_only_notice'));

        session()->flush();

        Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign->fresh()])
            ->assertDontSee($url)
            ->assertSee(__('recruitment.public_link.hash_only_notice'));
    }

    /** A corrected advert reaches the shared link without changing its address. */
    public function test_refreshing_a_stale_link_keeps_its_address(): void
    {
        [$campaign, $hr, , $jobPost] = $this->makePublishedCampaign();
        $service = app(PublicRecruitmentLinkService::class);
        $result = $service->generate($campaign, $hr, now()->addDays(5));
        $link = $result['link'];

        $newer = $jobPost->versions()->create([
            'version_no' => 2,
            'title_en' => "We're Hiring — IT Specialist",
            'title_ar' => 'مطلوب متطوع — اختصاصي تقنية معلومات',
            'body_en' => 'Corrected content',
            'body_ar' => 'محتوى مصحح',
            'public_payload_json' => [
                'template' => 'hope_center_hiring_v1',
                'city' => 'HOMS',
                'program' => 'DISC',
                'job_title' => 'IT Specialist',
                'outputs' => [],
            ],
            'status' => 'published',
            'published_at' => now(),
        ]);
        $jobPost->forceFill(['current_version_id' => $newer->id])->save();

        Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign->fresh()])
            ->assertSee(__('recruitment.public_link.stale'))
            ->call('refreshVersions')
            ->assertSet('notice', __('recruitment.public_link.refreshed'))
            ->assertDontSee(__('recruitment.public_link.stale'));

        $fresh = $link->fresh();
        $this->assertSame($link->token_hash, $fresh->token_hash);
        $this->assertSame($newer->id, (int) $fresh->job_post_version_id);
        $this->get(route('careers.show', ['token' => $result['token']]))->assertOk();
    }

    /** @return array{HiringCampaign, User, RecruitmentRequest, JobPost, ApplicationForm} */
    public function test_an_internal_campaign_asks_visitors_to_sign_in_before_showing_the_form(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::Internal,
        );

        $this->get(route('careers.show', $result['token']))
            ->assertOk()
            ->assertSee(__('careers.internal.badge'))
            ->assertSee(__('careers.internal.sign_in_title'))
            ->assertSee('data-internal-sign-in', false)
            ->assertDontSee('name="submission_key"', false);

        $back = route('careers.show', ['token' => $result['token'], 'lang' => 'ar']);
        $response = $this->get(route('careers.sign-in', ['token' => $result['token'], 'lang' => 'ar']));
        $response->assertRedirect();
        $this->assertStringContainsString('login', (string) $response->headers->get('Location'));
        $this->assertSame($back, session('url.intended'));

        // Signed in already: straight back to the vacancy.
        $this->actingAs($hr)
            ->get(route('careers.sign-in', ['token' => $result['token'], 'lang' => 'ar']))
            ->assertRedirect($back);
    }

    public function test_signed_in_staff_find_their_details_already_filled_in(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::Internal,
        );
        $staff = $this->staffMember();

        $this->actingAs($staff)
            ->get(route('careers.show', $result['token']))
            ->assertOk()
            ->assertSee('data-internal-state="ready"', false)
            ->assertSee(__('careers.internal.applying_as'))
            ->assertSee('Rami Haddad')
            ->assertSee('data-recruitment-wizard', false)
            ->assertSee('value="Rami Haddad"', false)
            ->assertSee('value="rami.haddad@hopecenter.test"', false)
            ->assertSee('value="+963 944 000 111"', false)
            ->assertSee('readonly', false)
            ->assertSee(__('careers.internal.work_email_note'));
    }

    public function test_a_staff_application_is_filed_under_the_work_account_and_linked_to_the_employee(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::Internal,
        );
        $staff = $this->staffMember();

        // Whatever address is posted, the application belongs to the account.
        $this->actingAs($staff)
            ->post(route('careers.submit', $result['token']), $this->validSubmission('someone.else@example.com', 'Rami H.'))
            ->assertStatus(202);

        $application = RecruitmentApplication::query()->sole();
        $applicant = $application->applicant;
        $this->assertSame('rami.haddad@hopecenter.test', $application->email_normalized);
        $this->assertSame($staff->employee_id, (int) $applicant->employee_id);
        $this->assertSame('internal', $applicant->source);
        $this->assertNotNull($applicant->identity_verified_at);
        // Edits made on the form stay on the application, not the record.
        $this->assertSame('Rami H.', $applicant->full_name);
        $this->assertSame('Rami', $staff->profile->fresh()->first_name_en);

        $this->actingAs($staff)
            ->get(route('careers.show', $result['token']))
            ->assertOk()
            ->assertSee('data-internal-state="applied"', false)
            ->assertDontSee('name="submission_key"', false);
    }

    public function test_an_internal_campaign_refuses_applications_from_anyone_not_signed_in(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::Internal,
        );

        $this->post(route('careers.submit', $result['token']), $this->validSubmission('outsider@example.com'))
            ->assertUnprocessable()
            ->assertSee(__('careers.generic_submission_error'));

        $this->assertDatabaseCount('recruitment_applications', 0);
    }

    public function test_signing_in_changes_nothing_for_a_public_campaign(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $result = app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(10),
            RecruitmentCampaignLinkMode::PublicOpen,
        );
        $staff = $this->staffMember();

        $this->actingAs($staff)
            ->get(route('careers.show', $result['token']))
            ->assertOk()
            ->assertDontSee('data-internal-state', false)
            ->assertDontSee('rami.haddad@hopecenter.test');

        $this->actingAs($staff)
            ->post(route('careers.submit', $result['token']), $this->validSubmission('personal@example.com'))
            ->assertStatus(202);

        $applicant = RecruitmentApplication::query()->sole()->applicant;
        $this->assertSame('personal@example.com', $applicant->email_normalized);
        $this->assertNull($applicant->employee_id);
        $this->assertSame('public_link', $applicant->source);
    }

    private function staffMember(): User
    {
        $city = $this->makeCity('ALP', '001');
        $employee = $this->makeEmployee($city, 'rami-'.uniqid(), [
            'first_name_en' => 'Rami',
            'last_name_en' => 'Haddad',
            'personal_phone' => '+963 944 000 111',
            'dob' => '1994-05-12',
        ]);

        $user = $this->makeUser($city, 'employee', [], $employee);
        $user->forceFill(['email' => 'rami.haddad@hopecenter.test'])->save();

        return $user->fresh();
    }

    private function makePublishedCampaign(): array
    {
        $city = $this->makeCity('HMS', '002');
        $program = Program::query()->forceCreate(['name' => 'DISC', 'code' => 'D', 'slug' => 'disc-'.uniqid(), 'is_active' => true]);
        $department = Department::query()->forceCreate(['name' => 'Information Technology', 'code' => 'IT-'.uniqid()]);
        $jobLevel = JobLevel::query()->forceCreate(['name_en' => 'Specialist', 'name_ar' => 'اختصاصي', 'grade' => 6]);
        $position = Position::query()->forceCreate([
            'program_id' => $program->id,
            'city_id' => $city->id,
            'job_level_id' => $jobLevel->id,
            'department_id' => $department->id,
            'title_en' => 'IT Specialist',
            'title_ar' => 'اختصاصي تقنية معلومات',
            'max_emp' => 2,
            'exist_emp' => 0,
            'is_active' => true,
        ]);
        $requester = $this->makeUser($city, 'employee');
        $request = RecruitmentRequest::query()->forceCreate([
            'request_number' => 'REQ-'.strtoupper(uniqid()),
            'requester_user_id' => $requester->id,
            'request_type' => RecruitmentRequestType::CreatePosition,
            'mode' => RecruitmentRequestMode::Normal,
            'status' => RecruitmentRequestStatus::Applied,
            'scope_city_id' => $city->id,
            'target_position_id' => $position->id,
            'submitted_at' => now(),
            'applied_at' => now(),
        ]);
        RecruitmentRequestVersion::query()->forceCreate([
            'request_id' => $request->id,
            'version_no' => 1,
            'snapshot_json' => ['approved' => true],
            'created_by_user_id' => $requester->id,
            'submitted_at' => now(),
        ]);
        $vacancy = Vacancy::query()->forceCreate([
            'position_id' => $position->id,
            'created_from_request_id' => $request->id,
            'status' => 'open',
            'needed_count' => 1,
            'filled_count' => 0,
            'open_at' => now(),
        ]);
        $hr = $this->makeUser($city, 'HR-HMS', [
            'recruitment-campaigns.view',
            'recruitment-links.view',
            'recruitment-links.manage',
        ]);
        $campaign = HiringCampaign::query()->forceCreate([
            'vacancy_id' => $vacancy->id,
            'status' => HiringCampaignStatus::Open,
            'link_mode' => RecruitmentCampaignLinkMode::PublicOpen,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(14),
            'created_by_user_id' => $hr->id,
        ]);

        $jobPost = JobPost::query()->forceCreate([
            'campaign_id' => $campaign->id,
            'status' => 'published',
            'created_by_user_id' => $hr->id,
        ]);
        $jobVersion = $jobPost->versions()->create([
            'version_no' => 1,
            'title_en' => "We're Hiring — IT Specialist",
            'title_ar' => 'مطلوب متطوع — اختصاصي تقنية معلومات',
            'body_en' => 'Join the DISC team.',
            'body_ar' => 'انضم إلى فريق DISC.',
            'public_payload_json' => [
                'template' => 'hope_center_hiring_v1',
                'city' => 'HOMS',
                'program' => 'DISC',
                'job_title' => 'IT Specialist',
                'outputs' => [],
            ],
            'status' => 'published',
            'published_at' => now(),
        ]);
        $jobPost->forceFill(['current_version_id' => $jobVersion->id])->save();

        $form = ApplicationForm::query()->forceCreate([
            'campaign_id' => $campaign->id,
            'status' => 'published',
            'created_by_user_id' => $hr->id,
        ]);
        $formVersion = $form->versions()->create([
            'version_no' => 1,
            'fields_json' => [
                ['key' => 'full_name'],
                ['key' => 'email'],
            ],
            'privacy_notice_version' => 'v1',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $form->forceFill(['current_version_id' => $formVersion->id])->save();

        return [$campaign, $hr, $request, $jobPost, $form];
    }

    /** @return array<string, mixed> */
    private function validSubmission(
        string $email,
        string $fullName = 'Applicant Name',
        ?string $submissionKey = null,
    ): array {
        return [
            'locale' => 'en',
            'submission_key' => $submissionKey ?? bin2hex(random_bytes(32)),
            'answers' => [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => '+963 999 123 456',
                'cv' => UploadedFile::fake()->createWithContent(
                    'cv.pdf',
                    "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF\n",
                ),
                'privacy_consent' => '1',
            ],
        ];
    }
}
