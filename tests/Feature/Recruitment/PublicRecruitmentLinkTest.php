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

    public function test_generating_the_first_link_publishes_a_valid_draft_campaign(): void
    {
        [$campaign, $hr, $request] = $this->makePublishedCampaign();
        $campaign->forceFill(['status' => HiringCampaignStatus::Draft])->save();
        $hr->roles()->wherePivot('is_active', true)->firstOrFail()
            ->givePermissionTo(Permission::findOrCreate('recruitment-campaigns.manage', 'web'));

        $component = Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign])
            ->set('expiresAt', now()->addDays(5)->format('Y-m-d\TH:i'))
            ->call('generate')
            ->assertSet('notice', __('recruitment.public_link.generated_and_published'))
            ->assertSet('generatedUrl', fn ($url) => str_contains($url, '/careers/'));

        $this->assertSame(HiringCampaignStatus::Published, $campaign->fresh()->status);
        $this->assertDatabaseHas('recruitment_request_events', [
            'recruitment_request_id' => $request->id,
            'event_type' => RecruitmentEventType::CampaignStateChanged,
            'actor_user_id' => $hr->id,
        ]);

        $link = $campaign->publicLinks()->where('status', PublicRecruitmentLink::STATUS_ACTIVE)->firstOrFail();
        $token = basename((string) parse_url($component->get('generatedUrl'), PHP_URL_PATH));
        $this->get(route('careers.show', ['token' => $token]))->assertOk();
        $this->assertSame(PublicRecruitmentLink::STATUS_ACTIVE, $link->status);
    }

    public function test_link_permission_alone_cannot_publish_a_draft_campaign(): void
    {
        [$campaign, $hr] = $this->makePublishedCampaign();
        $campaign->forceFill(['status' => HiringCampaignStatus::Draft])->save();

        Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign])
            ->set('expiresAt', now()->addDays(5)->format('Y-m-d\TH:i'))
            ->call('generate')
            ->assertForbidden();

        $this->assertSame(HiringCampaignStatus::Draft, $campaign->fresh()->status);
        $this->assertSame(0, $campaign->publicLinks()->count());
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

        Livewire::actingAs($hr)
            ->test(Manager::class, ['campaign' => $campaign])
            ->set('expiresAt', now()->addDays(5)->format('Y-m-d\TH:i'))
            ->set('linkMode', RecruitmentCampaignLinkMode::InvitationOnly)
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

    /** @return array{HiringCampaign, User, RecruitmentRequest, JobPost, ApplicationForm} */
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
