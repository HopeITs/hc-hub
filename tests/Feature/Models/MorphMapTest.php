<?php

use App\Models\HrDepartment;
use App\Models\Role;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Models\Account;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\Donor;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\DonorReport;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\LibraryIndicator;
use Modules\Tracking\Models\MealCoverageAssignment;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\ProgramRequest;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Models\ProposalStatusNote;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\ReportFile;
use Modules\Tracking\Models\ReportTemplate;
use Modules\Tracking\Models\Result;
use Modules\Tracking\Models\Risk;
use Modules\Tracking\Models\RiskLibrary;
use Modules\Tracking\Models\StrategicPillar;
use Modules\Tracking\Models\Strategy;
use Modules\Tracking\Models\Supervision;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Models\TransfersTracking;

test('the morph map registers exactly the target sources, task sources, media owners, the permission model side and the activity subjects', function () {
    expect(Relation::morphMap())->toBe([
        'annual_plan' => AnnualPlan::class,
        'proposal' => Proposal::class,
        'proposal_status_note' => ProposalStatusNote::class,
        'project' => Project::class,
        'milestone' => Milestone::class,
        'program' => Program::class,
        'department' => HrDepartment::class,
        'user' => User::class,
        'task' => Task::class,
        'strategy' => Strategy::class,
        'risk' => Risk::class,
        'risk_library' => RiskLibrary::class,
        'indicator' => Indicator::class,
        'plan_indicator' => PlanIndicator::class,
        'result' => Result::class,
        'report' => Report::class,
        'donor' => Donor::class,
        'donor_proposal' => DonorProposal::class,
        'donor_report' => DonorReport::class,
        'transfer' => TransfersTracking::class,
        'account' => Account::class,
        'role' => Role::class,
        'library_indicator' => LibraryIndicator::class,
        'strategic_pillar' => StrategicPillar::class,
        'meal_coverage' => MealCoverageAssignment::class,
        'supervision' => Supervision::class,
        'monthly_report' => MonthlyReport::class,
        'report_template' => ReportTemplate::class,
        'proposal_location' => ProposalLocation::class,
        'program_request' => ProgramRequest::class,
    ]);
});

test('the morph map is enforced so an unmapped model has no morph class', function () {
    expect(Relation::requiresMorphMap())->toBeTrue()
        ->and((new AnnualPlan)->getMorphClass())->toBe('annual_plan')
        ->and((new Proposal)->getMorphClass())->toBe('proposal')
        ->and((new Project)->getMorphClass())->toBe('project')
        ->and((new Milestone)->getMorphClass())->toBe('milestone')
        ->and((new Program)->getMorphClass())->toBe('program')
        ->and((new HrDepartment)->getMorphClass())->toBe('department')
        ->and((new User)->getMorphClass())->toBe('user')
        ->and((new Task)->getMorphClass())->toBe('task')
        ->and((new Strategy)->getMorphClass())->toBe('strategy')
        ->and((new TransfersTracking)->getMorphClass())->toBe('transfer');

    (new ReportFile)->getMorphClass();
})->throws(ClassMorphViolationException::class);

test('each mapped key fits the fifty character source_type column', function () {
    foreach (array_keys(Relation::morphMap()) as $key) {
        expect(strlen($key))->toBeLessThanOrEqual(50);
    }
});
