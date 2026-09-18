<?php

namespace Tests\Feature\Recruitment;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType;
use App\Livewire\Recruitment\JobPosts\Editor;
use App\Models\Department;
use App\Models\HiringCampaign;
use App\Models\JobLevel;
use App\Models\JobPostVersion;
use App\Models\Position;
use App\Models\Program;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentRequestPositionDetail;
use App\Models\RecruitmentRequestVersion;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\RecruitmentJobPostService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class JobPostGeneratorTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_it_prefills_the_generator_from_the_approved_request_without_exposing_internal_data(): void
    {
        [$campaign, $hr, $request, $detail] = $this->makeCampaign();

        $state = app(RecruitmentJobPostService::class)->openEditor($campaign, $hr);
        $payload = $state['version']->public_payload_json;

        $this->assertSame([
            'city' => 'HOMS',
            'program' => 'DISC',
            'job_title' => 'IT Specialist',
        ], $state['values']);
        $this->assertSame(RecruitmentJobPostService::TEMPLATE, $payload['template']);
        $this->assertSame(2160, $payload['outputs']['story']['width']);
        $this->assertSame(3840, $payload['outputs']['story']['height']);
        $this->assertSame(2160, $payload['outputs']['post']['height']);
        $this->assertArrayNotHasKey('request_id', $payload);
        $this->assertArrayNotHasKey('salary_amount', $payload);
        $this->assertArrayNotHasKey('requester_user_id', $payload);

        app(RecruitmentJobPostService::class)->saveDraft($state['job_post'], [
            'city' => 'Aleppo',
            'program' => 'Study Zone',
            'job_title' => 'Project Assistant',
        ]);

        $this->assertSame('HOMS', $request->fresh()->scopeCity->name_en);
        $this->assertSame('IT Specialist', $detail->fresh()->role_title_en);
        $this->assertSame('ALEPPO', $state['job_post']->versions()->first()->public_payload_json['city']);
    }

    public function test_published_versions_are_immutable_and_republishing_without_changes_is_idempotent(): void
    {
        [$campaign, $hr] = $this->makeCampaign();
        $service = app(RecruitmentJobPostService::class);
        $state = $service->openEditor($campaign, $hr);

        $first = $service->publish($state['job_post'], [
            'city' => 'HOMS',
            'program' => 'DISC',
            'job_title' => 'IT Specialist',
        ], $hr);
        $same = $service->publish($state['job_post'], [
            'city' => 'HOMS',
            'program' => 'DISC',
            'job_title' => 'IT Specialist',
        ], $hr);

        $this->assertSame($first->id, $same->id);
        $this->assertDatabaseCount('job_post_versions', 1);
        $this->assertDatabaseCount('recruitment_request_events', 1);
        $this->assertDatabaseHas('recruitment_request_events', [
            'event_type' => RecruitmentEventType::JobPostPublished,
            'actor_user_id' => $hr->id,
        ]);

        $draft = $service->saveDraft($state['job_post'], [
            'city' => 'ALEPPO',
            'program' => 'STUDY ZONE',
            'job_title' => 'Project Assistant',
        ]);

        $this->assertSame(2, $draft->version_no);
        $this->assertSame('draft', $draft->status);
        $this->assertSame('IT Specialist', $first->fresh()->public_payload_json['job_title']);
        $this->assertSame($first->id, $state['job_post']->fresh()->current_version_id);

        $second = $service->publish($state['job_post'], [
            'city' => 'ALEPPO',
            'program' => 'STUDY ZONE',
            'job_title' => 'Project Assistant',
        ], $hr);

        $this->assertSame(2, $second->version_no);
        $this->assertSame('published', $second->status);
        $this->assertSame($second->id, $state['job_post']->fresh()->current_version_id);
        $this->assertSame(2, JobPostVersion::query()->where('status', 'published')->count());
    }

    public function test_the_livewire_editor_is_available_only_to_hr_inside_the_request_scope(): void
    {
        [$campaign, $hr] = $this->makeCampaign();

        Livewire::actingAs($hr)
            ->test(Editor::class, ['campaign' => $campaign])
            ->assertSet('city', 'HOMS')
            ->assertSet('program', 'DISC')
            ->assertSet('jobTitle', 'IT Specialist')
            ->assertSee('2160 × 3840');

        $otherCity = $this->makeCity('ALP', '001');
        $otherHr = $this->makeUser($otherCity, 'HR-ALP', ['recruitment-job-posts.manage']);

        Livewire::actingAs($otherHr)
            ->test(Editor::class, ['campaign' => $campaign])
            ->assertForbidden();
    }

    public function test_a_non_approved_request_cannot_generate_a_job_post(): void
    {
        [$campaign, $hr, $request] = $this->makeCampaign();
        $request->forceFill(['status' => RecruitmentRequestStatus::UnderReview])->save();

        $this->expectException(DomainException::class);

        app(RecruitmentJobPostService::class)->openEditor($campaign, $hr);
    }

    /** @return array{HiringCampaign, User, RecruitmentRequest, RecruitmentRequestPositionDetail} */
    private function makeCampaign(): array
    {
        $city = $this->makeCity('HMS', '002');
        $city->forceFill(['name_en' => 'HOMS', 'name_ar' => 'حمص'])->save();
        $program = Program::query()->forceCreate([
            'name' => 'DISC',
            'code' => 'D',
            'slug' => 'disc-'.uniqid(),
            'is_active' => true,
        ]);
        $department = Department::query()->forceCreate([
            'name' => 'Information Technology',
            'code' => 'IT-'.uniqid(),
        ]);
        $jobLevel = JobLevel::query()->forceCreate([
            'name_en' => 'Specialist',
            'name_ar' => 'اختصاصي',
            'grade' => 6,
        ]);
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
        $requestVersion = RecruitmentRequestVersion::query()->forceCreate([
            'request_id' => $request->id,
            'version_no' => 1,
            'snapshot_json' => ['approved' => true],
            'created_by_user_id' => $requester->id,
            'submitted_at' => now(),
        ]);
        $detail = RecruitmentRequestPositionDetail::query()->forceCreate([
            'request_version_id' => $requestVersion->id,
            'position_id' => $position->id,
            'program_id' => $program->id,
            'role_title_en' => 'IT Specialist',
            'role_title_ar' => 'اختصاصي تقنية معلومات',
            'requested_headcount' => 1,
        ]);
        $vacancy = Vacancy::query()->forceCreate([
            'position_id' => $position->id,
            'created_from_request_id' => $request->id,
            'status' => 'open',
            'needed_count' => 1,
            'filled_count' => 0,
            'open_at' => now(),
        ]);
        $hr = $this->makeUser($city, 'HR-HMS', ['recruitment-job-posts.manage']);
        $campaign = HiringCampaign::query()->forceCreate([
            'vacancy_id' => $vacancy->id,
            'status' => HiringCampaignStatus::Draft,
            'link_mode' => 'public_open',
            'created_by_user_id' => $hr->id,
        ]);

        return [$campaign, $hr, $request, $detail];
    }
}
