<?php

namespace Tests\Feature\Recruitment;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentCampaignLinkMode;
use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType;
use App\Livewire\Recruitment\Campaigns\CampaignTable;
use App\Livewire\Recruitment\Campaigns\Create as CampaignCreate;
use App\Livewire\Recruitment\Campaigns\Show as CampaignShow;
use App\Models\ApplicationForm;
use App\Models\Department;
use App\Models\HiringCampaign;
use App\Models\JobLevel;
use App\Models\JobPost;
use App\Models\Position;
use App\Models\Program;
use App\Models\PublicRecruitmentLink;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentRequestVersion;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\HiringCampaignService;
use App\Services\PublicRecruitmentLinkService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class HiringCampaignLifecycleTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_scoped_hr_can_create_sequential_campaigns_for_an_available_vacancy(): void
    {
        [$vacancy, $hr, $request] = $this->makeVacancy();
        $campaign = app(HiringCampaignService::class)->create($hr, $vacancy, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);

        $this->assertSame(HiringCampaignStatus::Draft, $campaign->status);
        $this->assertSame($vacancy->id, $campaign->vacancy_id);
        $this->assertSame($hr->id, $campaign->created_by_user_id);
        $this->assertDatabaseHas('recruitment_request_events', [
            'recruitment_request_id' => $request->id,
            'event_type' => RecruitmentEventType::CampaignStateChanged,
            'actor_user_id' => $hr->id,
        ]);

        app(HiringCampaignService::class)->create($hr, $vacancy, [
            'starts_at' => now(),
            'ends_at' => now()->addDays(10),
        ]);

        $this->assertSame(2, $vacancy->campaigns()->count());
    }

    public function test_campaign_creation_rechecks_scope_vacancy_and_dates(): void
    {
        [$vacancy] = $this->makeVacancy();
        $otherCity = $this->makeCity('ALP', '001');
        $otherHr = $this->makeUser($otherCity, 'HR-ALP', ['recruitment-campaigns.manage']);

        try {
            app(HiringCampaignService::class)->create($otherHr, $vacancy, [
                'starts_at' => now(),
                'ends_at' => now()->addWeek(),
            ]);
            $this->fail('Out-of-scope HR created a campaign.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('hiring_campaigns', 0);
        }

        $vacancy->forceFill(['filled_count' => $vacancy->needed_count])->save();
        $localHr = $this->makeUser($vacancy->createdFromRequest->scopeCity, 'HR-HMS', ['recruitment-campaigns.manage']);

        $this->expectException(ValidationException::class);
        app(HiringCampaignService::class)->create($localHr, $vacancy, [
            'starts_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);
    }

    public function test_campaign_opens_only_when_schedule_assets_and_link_are_ready(): void
    {
        [$vacancy, $hr] = $this->makeVacancy();
        $service = app(HiringCampaignService::class);
        $campaign = $service->create($hr, $vacancy, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->forceFill(['status' => HiringCampaignStatus::Published])->save();

        try {
            $service->open($hr, $campaign);
            $this->fail('Campaign opened without its published assets.');
        } catch (ValidationException) {
            $this->assertSame(HiringCampaignStatus::Published, $campaign->fresh()->status);
        }

        [$campaign, $token] = $this->attachPublishedAssetsAndLink($campaign, $hr);
        $opened = $service->open($hr, $campaign);

        $this->assertSame(HiringCampaignStatus::Open, $opened->status);
        app(PublicRecruitmentLinkService::class)->validateSubmissionShell($token, [
            'locale' => 'en',
            'answers' => ['full_name' => 'Applicant One', 'email' => 'applicant@example.com'],
        ]);
        $this->assertTrue(true);
    }

    public function test_closing_blocks_submission_and_disabling_also_revokes_links(): void
    {
        [$vacancy, $hr] = $this->makeVacancy();
        $service = app(HiringCampaignService::class);
        $campaign = $service->create($hr, $vacancy, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->forceFill(['status' => HiringCampaignStatus::Published])->save();
        [$campaign, $token, $link] = $this->attachPublishedAssetsAndLink($campaign, $hr);
        $campaign = $service->open($hr, $campaign);

        $closed = $service->close($hr, $campaign);
        $this->assertSame(HiringCampaignStatus::Closed, $closed->status);
        $this->assertSame($hr->id, $closed->closed_by_user_id);

        try {
            app(PublicRecruitmentLinkService::class)->validateSubmissionShell($token, [
                'locale' => 'en',
                'answers' => ['full_name' => 'Applicant One', 'email' => 'applicant@example.com'],
            ]);
            $this->fail('A closed campaign accepted a submission.');
        } catch (\App\Exceptions\PublicRecruitmentSubmissionException) {
            $this->assertSame(PublicRecruitmentLink::STATUS_ACTIVE, $link->fresh()->status);
        }

        [$secondVacancy, $secondHr] = $this->makeVacancy('DMS', '003');
        $disabled = $service->create($secondHr, $secondVacancy, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $disabled->forceFill(['status' => HiringCampaignStatus::Published])->save();
        [$disabled, , $disabledLink] = $this->attachPublishedAssetsAndLink($disabled, $secondHr);

        $disabled = $service->disable($secondHr, $disabled, 'Suspicious automated traffic.');
        $this->assertSame(HiringCampaignStatus::Disabled, $disabled->status);
        $this->assertSame(PublicRecruitmentLink::STATUS_DISABLED, $disabledLink->fresh()->status);
    }

    public function test_scheduler_opens_ready_campaigns_and_expires_finished_campaigns(): void
    {
        [$vacancy, $hr] = $this->makeVacancy();
        $service = app(HiringCampaignService::class);
        $ready = $service->create($hr, $vacancy, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $ready->forceFill(['status' => HiringCampaignStatus::Published])->save();
        [$ready] = $this->attachPublishedAssetsAndLink($ready, $hr);

        [$unreadyVacancy, $unreadyHr] = $this->makeVacancy('HMS-PENDING', '103');
        $unready = $service->create($unreadyHr, $unreadyVacancy, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $unready->forceFill(['status' => HiringCampaignStatus::Published])->save();

        [$pastVacancy, $pastHr] = $this->makeVacancy('LAT', '004');
        $expired = $service->create($pastHr, $pastVacancy, [
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->addDay(),
        ]);
        $expired->forceFill([
            'status' => HiringCampaignStatus::Open,
            'ends_at' => now()->subMinute(),
        ])->save();

        $result = $service->syncScheduledStates();

        $this->assertSame(['opened' => 1, 'expired' => 1], $result);
        $this->assertSame(HiringCampaignStatus::Open, $ready->fresh()->status);
        $this->assertSame(HiringCampaignStatus::Published, $unready->fresh()->status);
        $this->assertSame(HiringCampaignStatus::Expired, $expired->fresh()->status);
    }

    public function test_terminal_campaign_cannot_generate_a_new_public_link(): void
    {
        [$vacancy, $hr] = $this->makeVacancy();
        $campaign = app(HiringCampaignService::class)->create($hr, $vacancy, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        [$campaign] = $this->attachPublishedAssetsAndLink($campaign, $hr, false);
        $campaign->forceFill(['status' => HiringCampaignStatus::Closed])->save();

        $this->expectException(DomainException::class);
        app(PublicRecruitmentLinkService::class)->generate(
            $campaign,
            $hr,
            now()->addDays(3),
            RecruitmentCampaignLinkMode::PublicOpen,
        );
    }

    public function test_campaign_screen_lists_only_scoped_campaigns_and_available_vacancies(): void
    {
        [$localVacancy, $localHr] = $this->makeVacancy();
        [$remoteVacancy, $remoteHr] = $this->makeVacancy('ALP', '001');
        $service = app(HiringCampaignService::class);

        $localCampaign = $service->create($localHr, $localVacancy, [
            'starts_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);
        $service->create($remoteHr, $remoteVacancy, [
            'starts_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);
        [$otherLocalVacancy] = $this->makeVacancy('HMS-OTHER', '102');
        $otherLocalVacancy->createdFromRequest->forceFill([
            'scope_city_id' => $localVacancy->createdFromRequest->scope_city_id,
        ])->save();
        $otherLocalVacancy->position->forceFill([
            'city_id' => $localVacancy->position->city_id,
        ])->save();

        Livewire::actingAs($localHr)
            ->test(CampaignTable::class)
            ->assertSee($localCampaign->vacancy->position->title)
            ->assertDontSee($remoteVacancy->position->title);

        Livewire::actingAs($localHr)
            ->test(CampaignCreate::class)
            ->assertSee('value="'.$otherLocalVacancy->id.'"', false)
            ->assertSee('value="'.$localVacancy->id.'"', false);
    }

    public function test_campaign_screen_creates_campaign_and_redirects_to_publishing(): void
    {
        [$vacancy, $hr] = $this->makeVacancy();

        $screen = Livewire::actingAs($hr)
            ->test(CampaignCreate::class)
            ->set('vacancyId', $vacancy->id)
            ->set('startsAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('endsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $campaign = HiringCampaign::query()->sole();
        $screen->assertRedirect(route('recruitment.campaigns.show', ['campaignId' => $campaign->id]));

        Livewire::actingAs($hr)
            ->test(CampaignCreate::class)
            ->set('vacancyId', $vacancy->id)
            ->set('startsAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('endsAt', now()->addWeek()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('hiring_campaigns', 2);
        $this->assertSame(HiringCampaignStatus::Draft, $campaign->status);
    }

    public function test_campaign_screen_runs_open_close_and_disable_actions(): void
    {
        [$vacancy, $hr] = $this->makeVacancy();
        $service = app(HiringCampaignService::class);
        $campaign = $service->create($hr, $vacancy, [
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->forceFill(['status' => HiringCampaignStatus::Published])->save();
        [$campaign] = $this->attachPublishedAssetsAndLink($campaign, $hr);

        Livewire::actingAs($hr)
            ->test(CampaignShow::class, ['campaignId' => $campaign->id])
            ->call('act', 'open')
            ->assertHasNoErrors()
            ->set('reason', 'The campaign has received enough applications.')
            ->call('act', 'close')
            ->assertHasNoErrors();

        $this->assertSame(HiringCampaignStatus::Closed, $campaign->fresh()->status);

        [$secondVacancy, $secondHr] = $this->makeVacancy('DMS', '003');
        $second = $service->create($secondHr, $secondVacancy, [
            'starts_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);

        Livewire::actingAs($secondHr)
            ->test(CampaignShow::class, ['campaignId' => $second->id])
            ->set('reason', 'Vacancy was withdrawn by HR.')
            ->call('act', 'disable')
            ->assertHasNoErrors();

        $this->assertSame(HiringCampaignStatus::Disabled, $second->fresh()->status);
    }

    public function test_view_only_hr_can_browse_but_cannot_manage_campaigns(): void
    {
        [$vacancy, $hr] = $this->makeVacancy();
        $campaign = app(HiringCampaignService::class)->create($hr, $vacancy, [
            'starts_at' => now(),
            'ends_at' => now()->addWeek(),
        ]);
        $hr->roles()->first()->revokePermissionTo('recruitment-campaigns.manage');
        $viewer = $this->makeUser($vacancy->createdFromRequest->scopeCity, 'HR-HMS', [
            'recruitment-campaigns.view',
        ]);

        Livewire::actingAs($viewer)
            ->test(CampaignShow::class, ['campaignId' => $campaign->id])
            ->assertSee($campaign->vacancy->position->title)
            ->assertDontSee(__('recruitment-campaign.save_window'))
            ->call('act', 'disable')
            ->assertForbidden();
    }

    /** @return array{Vacancy, User, RecruitmentRequest} */
    private function makeVacancy(string $slug = 'HMS', string $code = '002'): array
    {
        $city = $this->makeCity($slug, $code);
        $program = Program::query()->forceCreate([
            'name' => 'DISC-'.$slug, 'code' => 'D-'.$slug, 'slug' => 'disc-'.strtolower($slug).'-'.uniqid(), 'is_active' => true,
        ]);
        $department = Department::query()->forceCreate([
            'name' => 'Information Technology '.$slug, 'code' => 'IT-'.$slug.'-'.uniqid(),
        ]);
        $jobLevel = JobLevel::query()->forceCreate([
            'name_en' => 'Specialist '.$slug, 'name_ar' => 'اختصاصي '.$slug, 'grade' => 6,
        ]);
        $position = Position::query()->forceCreate([
            'program_id' => $program->id,
            'city_id' => $city->id,
            'job_level_id' => $jobLevel->id,
            'department_id' => $department->id,
            'title_en' => 'IT Specialist '.$slug,
            'title_ar' => 'اختصاصي تقنية معلومات '.$slug,
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
            'open_at' => now()->subMinute(),
        ]);
        $hr = $this->makeUser($city, 'HR-'.$slug, [
            'recruitment-campaigns.view',
            'recruitment-campaigns.manage',
            'recruitment-links.view',
            'recruitment-links.manage',
        ]);

        return [$vacancy, $hr, $request];
    }

    /** @return array{HiringCampaign, string, PublicRecruitmentLink} */
    private function attachPublishedAssetsAndLink(HiringCampaign $campaign, User $hr, bool $createLink = true): array
    {
        $jobPost = JobPost::query()->forceCreate([
            'campaign_id' => $campaign->id,
            'status' => 'published',
            'created_by_user_id' => $hr->id,
        ]);
        $jobVersion = $jobPost->versions()->create([
            'version_no' => 1,
            'title_en' => 'IT Specialist',
            'title_ar' => 'اختصاصي تقنية معلومات',
            'body_en' => 'Join our team.',
            'body_ar' => 'انضم إلى فريقنا.',
            'public_payload_json' => ['city' => 'HOMS', 'program' => 'DISC', 'job_title' => 'IT Specialist'],
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
            'fields_json' => [['key' => 'full_name'], ['key' => 'email']],
            'privacy_notice_version' => 'v1',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $form->forceFill(['current_version_id' => $formVersion->id])->save();

        $token = bin2hex(random_bytes(32));
        $link = new PublicRecruitmentLink;
        if ($createLink) {
            $link = PublicRecruitmentLink::query()->forceCreate([
                'campaign_id' => $campaign->id,
                'job_post_version_id' => $jobVersion->id,
                'form_version_id' => $formVersion->id,
                'token_hash' => hash('sha256', $token),
                'status' => PublicRecruitmentLink::STATUS_ACTIVE,
                'expires_at' => min(now()->addDays(3), $campaign->ends_at),
                'created_by_user_id' => $hr->id,
            ]);
        }

        return [$campaign->fresh(), $token, $link];
    }
}
