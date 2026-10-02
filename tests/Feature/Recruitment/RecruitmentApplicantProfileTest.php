<?php

namespace Tests\Feature\Recruitment;

use App\Enums\HiringCampaignStatus;
use App\Enums\RecruitmentApplicationStatus as Status;
use App\Enums\RecruitmentFileScanStatus;
use App\Livewire\Recruitment\Applicants\ApplicationTable;
use App\Livewire\Recruitment\Applicants\Index;
use App\Livewire\Recruitment\Applicants\Notes;
use App\Livewire\Recruitment\Applicants\Show;
use App\Models\City;
use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentApplicantFile;
use App\Models\RecruitmentApplicantNote;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentApplicationDecision;
use App\Models\Role;
use App\Models\User;
use App\Services\RecruitmentApplicantNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use LogicException;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use ReflectionClass;
use ReflectionProperty;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BuildsRecruitmentApplications;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class RecruitmentApplicantProfileTest extends TestCase
{
    use BuildsRecruitmentApplications, CreatesHrFixtures, RefreshDatabase;

    private City $homs;

    private City $damascus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);

        $this->homs = $this->makeCity('HMS', '002');
        $this->damascus = $this->makeCity('DMS', '003');
    }

    // ------------------------------------------------------------------
    // List
    // ------------------------------------------------------------------

    public function test_list_shows_only_applications_in_scope_and_flags_earlier_applicants(): void
    {
        $returning = $this->makeApplicant('Returning Rana');
        $damascusOnly = $this->makeApplicant('Damascus Dani');
        $this->applyTo($this->makeCampaignIn($this->damascus), $returning, ['submitted_at' => now()->subDays(5)]);
        $this->applyTo($this->makeCampaignIn($this->homs), $returning);
        $this->applyTo($this->makeCampaignIn($this->damascus), $damascusOnly);

        $this->actingAs($this->hr($this->homs));
        Livewire::test(ApplicationTable::class)
            ->assertSee('Returning Rana')
            ->assertSee(__('recruitment-applicant.previous_applicant_flag'))
            ->assertDontSee('Damascus Dani');

        // In Damascus the returning applicant's row is their first application.
        $this->actingAs($this->hr($this->damascus));
        Livewire::test(ApplicationTable::class)
            ->assertSee('Returning Rana')
            ->assertSee('Damascus Dani')
            ->assertDontSee(__('recruitment-applicant.previous_applicant_flag'));
    }

    public function test_filters_narrow_by_status_campaign_and_source(): void
    {
        $first = $this->makeCampaignIn($this->homs, title: 'Teacher');
        $second = $this->makeCampaignIn($this->homs, HiringCampaignStatus::Closed, 'Driver');
        $this->applyTo($first, $this->makeApplicant('Shortlisted Sami'), ['status' => Status::Shortlisted]);
        $this->applyTo($second, $this->makeApplicant('Submitted Suha', source: 'walk_in'));
        $this->applyTo($second, $this->makeApplicant('Shortlisted Shadi'), ['status' => Status::Shortlisted]);

        $this->actingAs($this->hr($this->homs));

        Livewire::test(ApplicationTable::class)
            ->set('filters.status', Status::Shortlisted)
            ->assertSee('Shortlisted Sami')
            ->assertDontSee('Submitted Suha');

        Livewire::test(ApplicationTable::class)
            ->call('filterByStatus', Status::Shortlisted)
            ->assertSee('Shortlisted Sami')
            ->assertDontSee('Submitted Suha')
            ->assertSee(__('recruitment-applicant.campaign_number', ['number' => $first->id]));

        Livewire::test(ApplicationTable::class, [
            'stageStatus' => Status::Shortlisted,
            'campaignFilter' => $first->id,
        ])
            ->assertSee('Shortlisted Sami')
            ->assertDontSee('Shortlisted Shadi')
            ->assertDontSee('Submitted Suha');

        Livewire::test(Index::class)
            ->assertSee(__('recruitment-applicant.campaign_number', ['number' => $first->id]))
            ->call('filterByStatus', Status::Shortlisted)
            ->set('activeCampaignId', $first->id)
            ->assertSet('activeStatus', Status::Shortlisted)
            ->assertSet('activeCampaignId', $first->id);

        Livewire::test(ApplicationTable::class)
            ->set('filters.campaign_id', $second->id)
            ->assertSee('Submitted Suha')
            ->assertSee('Shortlisted Shadi')
            // A closed campaign's applications stay listed, marked as closed.
            ->assertSee(HiringCampaignStatus::getLocalizedName(HiringCampaignStatus::Closed)['value'])
            ->assertDontSee('Shortlisted Sami');

        Livewire::test(ApplicationTable::class)
            ->set('filters.applicant_source', 'walk_in')
            ->assertSee('Submitted Suha')
            ->assertDontSee('Shortlisted Sami');
    }

    public function test_list_is_refused_without_the_applicant_permission(): void
    {
        // Managers follow campaigns, but applicant records are HR's.
        $this->actingAs($this->makeUser($this->homs, 'manager', ['recruitment-campaigns.view']));

        Livewire::test(ApplicationTable::class)->assertForbidden();
        $this->get(route('recruitment.applicants.index'))->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Profile and IDOR
    // ------------------------------------------------------------------

    public function test_profile_is_a_404_outside_scope_and_403_without_permission(): void
    {
        $applicant = $this->makeApplicant('Homs Hala');
        $this->applyTo($this->makeCampaignIn($this->homs), $applicant);

        $this->actingAs($this->hr($this->homs))
            ->get(route('recruitment.applicants.show', ['applicantId' => $applicant->id]))
            ->assertOk()
            ->assertSee('Homs Hala');

        // Another governorate guessing the id learns nothing, not even that it exists.
        $this->actingAs($this->hr($this->damascus));
        Livewire::test(Show::class, ['applicantId' => $applicant->id])->assertNotFound();
        Livewire::test(Notes::class, ['applicantId' => $applicant->id])->assertNotFound();

        $this->actingAs($this->makeUser($this->homs, 'employee'))
            ->get(route('recruitment.applicants.show', ['applicantId' => $applicant->id]))
            ->assertForbidden();
    }

    public function test_profile_shows_only_in_scope_applications_and_notes(): void
    {
        $applicant = $this->makeApplicant('Split Salma');
        $homsApplication = $this->applyTo($this->makeCampaignIn($this->homs, title: 'Homs Role'), $applicant);
        $damascusApplication = $this->applyTo($this->makeCampaignIn($this->damascus, title: 'Damascus Role'), $applicant);

        $notes = app(RecruitmentApplicantNoteService::class);
        $notes->add($this->hr($this->damascus), $applicant, $damascusApplication, 'Damascus-only remark');
        $homsHr = $this->hr($this->homs);
        $notes->add($homsHr, $applicant, $homsApplication, 'Homs remark');

        $this->actingAs($homsHr);

        Livewire::test(Show::class, ['applicantId' => $applicant->id])
            ->assertSee('Homs Role')
            ->assertDontSee('Damascus Role')
            ->assertSee(trans_choice('recruitment-applicant.hidden_applications', 1, ['count' => 1]))
            ->assertSee(__('recruitment-applicant.previous_applicant_flag'));

        Livewire::test(Notes::class, ['applicantId' => $applicant->id])
            ->assertSee('Homs remark')
            ->assertDontSee('Damascus-only remark');
    }

    public function test_profile_uses_campaign_city_when_the_applicant_city_is_blank(): void
    {
        $applicant = $this->makeApplicant('Cityless Clara');
        $this->applyTo($this->makeCampaignIn($this->homs, title: 'Homs Role'), $applicant);
        $this->actingAs($this->hr($this->homs));

        Livewire::test(Show::class, ['applicantId' => $applicant->id])
            ->assertSee('Cityless Clara')
            ->assertSee($this->homs->name);
    }

    // ------------------------------------------------------------------
    // Notes
    // ------------------------------------------------------------------

    public function test_a_note_records_its_actor_and_cannot_target_another_scopes_application(): void
    {
        $applicant = $this->makeApplicant();
        $homsApplication = $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $damascusApplication = $this->applyTo($this->makeCampaignIn($this->damascus), $applicant);
        $hr = $this->hr($this->homs);
        $this->actingAs($hr);

        Livewire::test(Notes::class, ['applicantId' => $applicant->id])
            ->set('applicationId', $damascusApplication->id)
            ->set('body', 'Trying the neighbour')
            ->call('addNote')
            ->assertHasErrors('applicationId')
            ->set('applicationId', $homsApplication->id)
            ->set('body', 'Strong portfolio')
            ->call('addNote')
            ->assertHasNoErrors();

        $note = RecruitmentApplicantNote::query()->sole();
        $this->assertSame($hr->id, (int) $note->user_id);
        $this->assertSame($homsApplication->id, (int) $note->application_id);
        $this->assertNotNull($note->created_at);
        $this->assertSame($hr->id, $note->actor->id);

        // Writing a note moved nothing.
        $this->assertSame(Status::Submitted, $homsApplication->fresh()->status);
        $this->assertSame(0, RecruitmentApplicationDecision::query()->count());
    }

    public function test_the_notes_component_has_no_way_to_carry_a_status(): void
    {
        $surface = collect((new ReflectionClass(Notes::class))->getProperties(ReflectionProperty::IS_PUBLIC))
            ->map->getName()
            ->merge(collect((new ReflectionClass(Notes::class))->getMethods())->map->getName())
            ->filter(fn (string $name): bool => str_contains(strtolower($name), 'status'));

        $this->assertSame([], $surface->values()->all());
    }

    public function test_notes_are_immutable_and_cannot_be_deleted_outside_the_service(): void
    {
        $applicant = $this->makeApplicant();
        $application = $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $note = app(RecruitmentApplicantNoteService::class)->add($this->hr($this->homs), $applicant, $application, 'Original');

        try {
            $note->forceFill(['body' => 'Rewritten'])->save();
            $this->fail('A note body was edited.');
        } catch (LogicException) {
            // Expected.
        }

        $this->expectException(LogicException::class);
        $note->fresh()->delete();
    }

    public function test_deleting_a_sensitive_note_needs_a_reason_and_leaves_an_audit_entry(): void
    {
        $applicant = $this->makeApplicant();
        $application = $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $hr = $this->hr($this->homs);
        $note = app(RecruitmentApplicantNoteService::class)->add($hr, $applicant, $application, 'Medical detail', sensitive: true);
        $this->actingAs($hr);

        $screen = Livewire::test(Notes::class, ['applicantId' => $applicant->id])
            ->call('confirmDelete', $note->id)
            ->call('deleteNote')
            ->assertHasErrors('deletionReason');
        $this->assertFalse($note->fresh()->trashed());

        $screen->set('deletionReason', 'Recorded against the wrong applicant')
            ->call('deleteNote')
            ->assertHasNoErrors()
            ->assertDontSee('Medical detail');

        $deleted = RecruitmentApplicantNote::withTrashed()->findOrFail($note->id);
        $this->assertTrue($deleted->trashed());
        $this->assertSame('Medical detail', $deleted->body, 'The note row is kept for the audit.');
        $this->assertSame($hr->id, (int) $deleted->deleted_by_user_id);

        $audit = Activity::query()
            ->where('log_name', RecruitmentApplicantNoteService::AUDIT_LOG)
            ->where('description', 'recruitment_note.deleted')
            ->sole();
        $this->assertSame($note->id, (int) $audit->subject_id);
        $this->assertSame($hr->id, (int) $audit->causer_id);
        $this->assertSame(hash('sha256', 'Medical detail'), $audit->getExtraProperty('body_sha256'));
        $this->assertStringNotContainsString('Medical detail', json_encode($audit->properties));
    }

    public function test_deleting_notes_needs_its_own_permission(): void
    {
        $applicant = $this->makeApplicant();
        $application = $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $hr = $this->without($this->hr($this->homs), ['recruitment-applicants.delete-notes']);
        $note = app(RecruitmentApplicantNoteService::class)->add($hr, $applicant, $application, 'Keep me');
        $this->actingAs($hr);

        Livewire::test(Notes::class, ['applicantId' => $applicant->id])
            ->call('confirmDelete', $note->id)
            ->assertForbidden();

        $this->assertFalse($note->fresh()->trashed());
    }

    // ------------------------------------------------------------------
    // Status changes
    // ------------------------------------------------------------------

    public function test_status_change_is_a_separate_action_recorded_as_a_decision(): void
    {
        $applicant = $this->makeApplicant();
        $application = $this->applyTo($this->makeCampaignIn($this->homs, HiringCampaignStatus::Closed), $applicant);
        $hr = $this->hr($this->homs);
        $this->actingAs($hr);

        Livewire::test(Show::class, ['applicantId' => $applicant->id])
            ->call('beginStatusChange', $application->id, Status::Rejected)
            ->set('statusReason', 'Application does not meet the required language level.')
            ->call('changeStatus')
            ->assertHasErrors('statusReasonCode')
            ->set('statusReasonCode', 'other')
            ->call('changeStatus')
            ->assertHasNoErrors();

        $this->assertSame(Status::Rejected, $application->fresh()->status);
        $decision = RecruitmentApplicationDecision::query()->sole();
        $this->assertSame([Status::Submitted, Status::Rejected, $hr->id], [
            $decision->from_status, $decision->to_status, (int) $decision->decided_by_user_id,
        ]);
    }

    public function test_status_change_refuses_stale_screens_and_later_pipeline_states(): void
    {
        $applicant = $this->makeApplicant();
        $application = $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $this->actingAs($this->hr($this->homs));

        $screen = Livewire::test(Show::class, ['applicantId' => $applicant->id])
            ->call('beginStatusChange', $application->id, Status::Shortlisted);

        // Someone else moved it while this screen was open.
        DB::table('recruitment_applications')->where('id', $application->id)->update(['status' => Status::Hired]);

        $screen->set('statusReason', 'Review was completed while this screen was open.')
            ->call('changeStatus')->assertHasErrors('statusTarget');
        $this->assertSame(Status::Hired, $application->fresh()->status);
    }

    public function test_status_change_needs_its_own_permission(): void
    {
        $applicant = $this->makeApplicant();
        $application = $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $this->actingAs($this->without($this->hr($this->homs), ['recruitment-applicants.change-status']));

        Livewire::test(Show::class, ['applicantId' => $applicant->id])
            ->call('beginStatusChange', $application->id, Status::Shortlisted)
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // CVs
    // ------------------------------------------------------------------

    public function test_archiving_a_cv_without_a_vacancy_requires_a_reason_note(): void
    {
        // A talent-pool record: no application, so scope comes from its city.
        $applicant = $this->makeApplicant('Pool Paul', $this->homs, 'walk_in');
        $cv = $this->cvFor($applicant);
        $hr = $this->hr($this->homs);
        $this->actingAs($hr);

        Livewire::test(Show::class, ['applicantId' => $applicant->id])
            ->call('beginArchive', $cv->id)
            ->call('archiveCv')
            ->assertHasErrors('archiveReason')
            ->set('archiveReason', 'Kept for the next teaching intake')
            ->call('archiveCv')
            ->assertHasNoErrors();

        $cv->refresh();
        $this->assertTrue($cv->isArchived());
        $this->assertSame($hr->id, (int) $cv->archived_by_user_id);
        $this->assertSame(RecruitmentApplicantNote::TYPE_CV_ARCHIVE, $cv->archiveNote->note_type);
        $this->assertSame('Kept for the next teaching intake', $cv->archiveNote->body);
        $this->assertFalse(Gate::forUser($hr)->allows('delete', $cv->archiveNote), 'The justification cannot be deleted.');

        // Another governorate cannot reach the record at all.
        $this->actingAs($this->hr($this->damascus));
        Livewire::test(Show::class, ['applicantId' => $applicant->id])->assertNotFound();
    }

    public function test_a_cv_with_a_vacancy_archives_without_a_reason(): void
    {
        $applicant = $this->makeApplicant();
        $application = $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $cv = $this->cvFor($applicant, $application);
        $this->actingAs($this->hr($this->homs));

        Livewire::test(Show::class, ['applicantId' => $applicant->id])
            ->call('beginArchive', $cv->id)
            ->call('archiveCv')
            ->assertHasNoErrors();

        $this->assertTrue($cv->fresh()->isArchived());
        $this->assertNull($cv->fresh()->archive_note_id);
    }

    public function test_cv_download_needs_its_own_permission_and_scope(): void
    {
        $applicant = $this->makeApplicant(city: $this->homs);
        $application = $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $cv = $this->cvFor($applicant, $application);
        $orphan = $this->cvFor($applicant);

        $hr = $this->hr($this->homs);
        $this->assertTrue(Gate::forUser($hr)->inspect('download', $cv)->allowed());

        // Managing campaigns — or seeing the applicant — is not reading their CV.
        $viewer = $this->without($hr, ['recruitment-applicants.download-cv']);
        $this->assertSame('missing_permission', Gate::forUser($viewer)->inspect('download', $cv)->code());
        $this->actingAs($viewer)
            ->get(route('recruitment.applicant-files.download', $cv->id))
            ->assertForbidden();

        $damascusHr = $this->hr($this->damascus);
        $this->assertSame('out_of_scope', Gate::forUser($damascusHr)->inspect('download', $cv)->code());
        $this->assertSame('out_of_scope', Gate::forUser($damascusHr)->inspect('download', $orphan)->code());
    }

    public function test_a_missing_cv_is_shown_as_missing_not_as_an_error(): void
    {
        $applicant = $this->makeApplicant('No File Nour');
        $this->applyTo($this->makeCampaignIn($this->homs), $applicant);
        $this->actingAs($this->hr($this->homs));

        Livewire::test(ApplicationTable::class)->assertSee(__('recruitment-applicant.cv_availability.missing'));
        Livewire::test(Show::class, ['applicantId' => $applicant->id])
            ->assertOk()
            ->assertSee(__('recruitment-applicant.cv_availability.missing'));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Governorate HR holding what the permission migration grants HR roles. */
    private function hr(City $city): User
    {
        return $this->makeUser($city, 'HR-'.$city->slug, [
            'recruitment-campaigns.view',
            ...array_map(
                fn (string $action): string => "recruitment-applicants.{$action}",
                config('access-control.groups.recruitment-applicants'),
            ),
        ]);
    }

    private function without(User $user, array $permissions): User
    {
        Role::findByName($user->myRole(), 'web')->revokePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    private function cvFor(RecruitmentApplicant $applicant, ?RecruitmentApplication $application = null): RecruitmentApplicantFile
    {
        return RecruitmentApplicantFile::query()->forceCreate([
            'applicant_id' => $applicant->id,
            'application_id' => $application?->id,
            'file_type' => RecruitmentApplicantFile::TYPE_CV,
            'scan_status' => RecruitmentFileScanStatus::Clean,
            'uploaded_at' => now(),
        ]);
    }
}
