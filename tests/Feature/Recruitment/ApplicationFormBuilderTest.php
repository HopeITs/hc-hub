<?php

namespace Tests\Feature\Recruitment;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType;
use App\Exceptions\StaleRecruitmentDraftException;
use App\Livewire\Recruitment\ApplicationForms\Editor;
use App\Models\Department;
use App\Models\HiringCampaign;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentRequestVersion;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\RecruitmentApplicationFormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class ApplicationFormBuilderTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    public function test_it_builds_and_publishes_an_immutable_safe_form_schema(): void
    {
        [$campaign, $hr] = $this->makeCampaign();
        $service = app(RecruitmentApplicationFormService::class);
        $state = $service->openEditor($campaign, $hr);

        $this->assertSame(['full_name', 'email', 'phone', 'city', 'experience', 'motivation', 'cv'], array_column($state['fields'], 'key'));
        $published = $service->publish(
            $state['application_form'], $state['fields'], 'privacy-2026-01', $hr, $state['version']->id, $state['fingerprint'],
        );
        $schema = $service->publicSchema($campaign->fresh());

        $this->assertSame('privacy-2026-01', $schema['privacy_notice_version']);
        $this->assertSame(['key', 'type', 'required', 'label'], array_keys($schema['fields'][0]));
        $this->assertArrayNotHasKey('locked', $schema['fields'][0]);
        $this->assertDatabaseHas('recruitment_request_events', [
            'event_type' => RecruitmentEventType::FormVersionPublished,
            'actor_user_id' => $hr->id,
        ]);

        $changed = $state['fields'];
        $changed[0]['label']['en'] = 'Legal full name';
        $draftState = $service->openEditor($campaign->fresh(), $hr);
        $draft = $service->saveDraft(
            $state['application_form'], $changed, 'privacy-2026-02', $draftState['version']->id, $draftState['fingerprint'],
        );

        $this->assertSame(2, $draft->version_no);
        $this->assertSame('Full name', $published->fresh()->fields_json[0]['label']['en']);
        $this->assertSame('Legal full name', $draft->fields_json[0]['label']['en']);
    }

    public function test_submission_is_validated_against_the_exact_published_version_and_rejects_unknown_fields(): void
    {
        [$campaign, $hr] = $this->makeCampaign();
        $service = app(RecruitmentApplicationFormService::class);
        $state = $service->openEditor($campaign, $hr);
        $version = $service->publish(
            $state['application_form'], $state['fields'], 'v1', $hr, $state['version']->id, $state['fingerprint'],
        );
        $answers = [
            'full_name' => 'Applicant Name', 'email' => 'applicant@example.com', 'phone' => '+963999000000',
            'city' => 'Homs', 'experience' => null, 'motivation' => null,
            'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ];

        $this->assertSame('Applicant Name', $service->validateSubmission($version, $answers)['full_name']);

        $this->expectException(ValidationException::class);
        $service->validateSubmission($version, [...$answers, 'internal_status' => 'accepted']);
    }

    public function test_it_detects_concurrent_edits_and_enforces_city_scope_in_the_editor(): void
    {
        [$campaign, $hr] = $this->makeCampaign();
        $service = app(RecruitmentApplicationFormService::class);
        $first = $service->openEditor($campaign, $hr);
        $second = $service->openEditor($campaign, $hr);
        $fields = $first['fields'];
        $fields[0]['label']['en'] = 'Changed';
        $service->saveDraft($first['application_form'], $fields, 'v1', $first['version']->id, $first['fingerprint']);

        try {
            $service->saveDraft($second['application_form'], $second['fields'], 'v1', $second['version']->id, $second['fingerprint']);
            $this->fail('A stale editor should not overwrite a newer draft.');
        } catch (StaleRecruitmentDraftException) {
            $this->assertTrue(true);
        }

        Livewire::actingAs($hr)->test(Editor::class, ['campaign' => $campaign])->assertSet('versionNumber', 1);
        $otherCity = $this->makeCity('ALP', '001');
        $otherHr = $this->makeUser($otherCity, 'HR-ALP', ['recruitment-forms.manage']);
        Livewire::actingAs($otherHr)->test(Editor::class, ['campaign' => $campaign])->assertForbidden();
    }

    /** @return array{HiringCampaign, User} */
    private function makeCampaign(): array
    {
        $city = $this->makeCity('HMS', '002');
        $program = Program::query()->forceCreate(['name' => 'DISC', 'code' => 'D', 'slug' => 'disc-'.uniqid(), 'is_active' => true]);
        $department = Department::query()->forceCreate(['name' => 'Information Technology', 'code' => 'IT-'.uniqid()]);
        $jobLevel = JobLevel::query()->forceCreate(['name_en' => 'Specialist', 'name_ar' => 'اختصاصي', 'grade' => 6]);
        $position = Position::query()->forceCreate([
            'program_id' => $program->id, 'city_id' => $city->id, 'job_level_id' => $jobLevel->id,
            'department_id' => $department->id, 'title_en' => 'IT Specialist', 'title_ar' => 'اختصاصي تقنية معلومات',
            'max_emp' => 2, 'exist_emp' => 0, 'is_active' => true,
        ]);
        $requester = $this->makeUser($city, 'employee');
        $request = RecruitmentRequest::query()->forceCreate([
            'request_number' => 'REQ-'.strtoupper(uniqid()), 'requester_user_id' => $requester->id,
            'request_type' => RecruitmentRequestType::CreatePosition, 'mode' => RecruitmentRequestMode::Normal,
            'status' => RecruitmentRequestStatus::Applied, 'scope_city_id' => $city->id,
            'target_position_id' => $position->id, 'submitted_at' => now(), 'applied_at' => now(),
        ]);
        RecruitmentRequestVersion::query()->forceCreate([
            'request_id' => $request->id, 'version_no' => 1, 'snapshot_json' => ['approved' => true],
            'created_by_user_id' => $requester->id, 'submitted_at' => now(),
        ]);
        $vacancy = Vacancy::query()->forceCreate([
            'position_id' => $position->id, 'created_from_request_id' => $request->id,
            'status' => 'open', 'needed_count' => 1, 'filled_count' => 0, 'open_at' => now(),
        ]);
        $hr = $this->makeUser($city, 'HR-HMS', ['recruitment-forms.manage']);
        $campaign = HiringCampaign::query()->forceCreate([
            'vacancy_id' => $vacancy->id, 'status' => HiringCampaignStatus::Draft,
            'link_mode' => 'public_open', 'created_by_user_id' => $hr->id,
        ]);

        return [$campaign, $hr];
    }
}
