<?php

use Modules\Hr\Models\Employee as HrEmployee;
use App\Models\Media;
use Database\Seeders\CoreDataSeeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Models\Account;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\Donor;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\EvidenceType;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\IndicatorProgress;
use Modules\Tracking\Models\LibraryIndicator;
use Modules\Tracking\Models\MealCoverageAssignment;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\MonthlyReportAnswer;
use Modules\Tracking\Models\NarrativeQuestion;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\ProgramRequest;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Models\ProposalReviewer;
use Modules\Tracking\Models\ProposalStatusNote;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\ReportFile;
use Modules\Tracking\Models\Result;
use Modules\Tracking\Models\Risk;
use Modules\Tracking\Models\RiskLibrary;
use Modules\Tracking\Models\Sector;
use Modules\Tracking\Models\Strategy;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Models\TaskAssignee;
use Modules\Tracking\Models\TaskAttachment;
use Modules\Tracking\Models\TransfersTracking;

beforeEach(fn () => insertHrSeedReferenceData());

test('the seeders populate every table in the schema', function () {
    $this->seed();

    $models = [
        User::class,
        Strategy::class,
        Donor::class,
        Program::class,
        AnnualPlan::class,
        Project::class,
        Proposal::class,
        DonorProposal::class,
        Risk::class,
        RiskLibrary::class,
        Milestone::class,
        Task::class,
        TaskAssignee::class,
        TaskAttachment::class,
        Media::class,
        Result::class,
        Indicator::class,
        IndicatorProgress::class,
        LibraryIndicator::class,
        PlanIndicator::class,
        Report::class,
        ReportFile::class,
        ProposalStatusNote::class,
        ProposalReviewer::class,
        ProposalLocation::class,
        Account::class,
        TransfersTracking::class,
        MealCoverageAssignment::class,
        MonthlyReport::class,
        MonthlyReportAnswer::class,
        Sector::class,
        EvidenceType::class,
        NarrativeQuestion::class,
    ];

    foreach ($models as $model) {
        expect($model::query()->count())->toBeGreaterThan(0, $model.' was not seeded');
    }

    expect(DB::table('project_proposal')->count())->toBeGreaterThanOrEqual(Project::query()->count());
    expect(DB::table('project_sector')->count())->toBeGreaterThanOrEqual(Project::query()->count());
});

test('seeded projects are placed in existing hr cities and seeding writes no cities', function () {
    $this->seed();

    expect(Project::query()->pluck('city_id')->unique()->diff(range(1, 17)))->toBeEmpty()
        ->and(DB::table('cities')->count())->toBe(17);
});

test('staff accounts carry the default role and assigned programmes have hr employee managers', function () {
    $this->seed();

    expect(User::query()->with('roles')->get()->map(fn (User $user): array => $user->roles->pluck('name')->all())->unique()->values()->all())
        ->toBe([[config('access-control.default_role')]]);

    foreach (Program::query()->whereNotNull('manager_id')->with('manager', 'projects', 'annualPlans')->get() as $program) {
        expect($program->manager)->toBeInstanceOf(HrEmployee::class);

        foreach ($program->projects->filter(fn (Project $project): bool => str_contains($project->name, ' Community Response ')) as $project) {
            expect($project->name)->toStartWith($program->name.' Community Response ');
        }

        foreach ($program->annualPlans as $plan) {
            expect($plan->manager_id)->toBe($program->manager_id);
        }
    }
});

test('lookup seeding reuses existing named donors', function () {
    $donor = Donor::factory()->create(['name' => 'UNICEF']);

    $this->seed(CoreDataSeeder::class);
    $this->seed(CoreDataSeeder::class);

    // 10 named lookups + 2 donors of the seeded proposals + the internal self-funding donor.
    $this->assertDatabaseCount('donors', 13);
    expect(Donor::query()->where('name', 'UNICEF')->sole()->id)->toBe($donor->id);
});

test('demo targets cover annual plans and proposals in the same programme', function () {
    $this->seed();

    expect(PlanIndicator::query()->where('source_type', 'annual_plan')->exists())->toBeTrue();
    $targets = PlanIndicator::query()->where('source_type', 'proposal')
        ->whereHasMorph('source', [Proposal::class], fn ($query) => $query->where('name', 'like', '% Emergency Response'))
        ->with('source.projects', 'project', 'indicator.result')->get();

    expect($targets)->not->toBeEmpty();

    foreach ($targets as $target) {
        expect($target->source)->toBeInstanceOf(Proposal::class)
            ->and($target->source->projects->contains('id', $target->project_id))->toBeTrue()
            ->and($target->project->program_id)->toBe($target->indicator->result->program_id);
    }
});

test('demo proposal dates fall within the latest strategy', function () {
    $this->seed();

    foreach (Proposal::query()->where('name', 'like', '% Emergency Response')->has('projects')->get() as $proposal) {
        $strategy = Strategy::query()->latest('id')->firstOrFail();

        expect($proposal->planned_date_begin->year)->toBe($strategy->start_year)
            ->and($proposal->planned_date_end->toDateString())->toBe($strategy->start_year.'-12-31')
            ->and($proposal->actual_date_begin->toDateString())->toBe($strategy->start_year.'-01-01');
    }
});

test('the seeded graph is navigable end to end', function () {
    $this->seed();

    $report = Report::query()->with('planIndicator.source', 'planIndicator.indicator.result.program')->firstOrFail();

    expect($report->planIndicator->source)->not->toBeNull()
        ->and($report->planIndicator->indicator->result->program)->not->toBeNull();

    $tranche = TransfersTracking::query()->whereNotNull('account_id')->with('donorProposal.donor', 'account')->firstOrFail();

    expect($tranche->donorProposal->donor)->not->toBeNull()
        ->and($tranche->account->is_active)->toBeTrue();
});

/**
 * Financial rows must add up. A factory state that derives fees or the net
 * from another column is easy to get subtly wrong \u2014 this fails loudly if the
 * seeded money ever stops reconciling.
 */
test('every seeded transfer reconciles', function () {
    $this->seed();

    $tranches = TransfersTracking::query()->get();

    expect($tranches)->not->toBeEmpty();

    foreach ($tranches as $tranche) {
        $expectedNet = round((float) $tranche->amount_received - (float) $tranche->fees, 2);

        expect(round((float) $tranche->net_amount_received, 2))
            ->toBe($expectedNet, "tranche {$tranche->id} does not reconcile")
            ->and((float) $tranche->net_amount_received)
            ->toBeLessThanOrEqual((float) $tranche->amount_received)
            ->and((float) $tranche->fees)->toBeGreaterThanOrEqual(0.0);
    }
});

test('seeding uses active hr employees only', function () {
    $inactiveId = insertHrEmployee(isActive: false);
    insertHrEmployee();
    insertHrUser($inactiveId, 'gone@example.org');
    insertHrUser(insertHrEmployee(), 'rana@example.org');

    $this->seed();

    expect(TaskAssignee::query()->where('employee_id', $inactiveId)->exists())->toBeFalse()
        ->and(Program::query()->where('manager_id', $inactiveId)->exists())->toBeFalse()
        ->and(Project::query()->where('user_id', $inactiveId)->exists())->toBeFalse()
        ->and(MealCoverageAssignment::query()->where('employee_id', $inactiveId)->exists())->toBeFalse();
});
