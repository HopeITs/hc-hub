<?php

use App\Models\Activity;
use Database\Seeders\AlWadiShowcaseSeeder;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\Result;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Models\TransfersTracking;

/**
 * @return array{0: Project, 1: Proposal}
 */
function alWadiProject(): array
{
    return Activity::withoutRecording(function (): array {
        foreach (['Rana', 'Sami', 'Lina', 'Omar'] as $name) {
            userWithRole('General Manager', insertHrEmployee(firstNameEn: $name, lastNameEn: 'Darwish'));
        }
        $project = Project::factory()->create(['slug' => 'al-wadi-training-center', 'user_id' => insertHrEmployee()]);
        $proposal = Proposal::factory()->create();
        $project->proposals()->attach($proposal->id);
        DonorProposal::factory()->create(['proposal_id' => $proposal->id, 'budget' => 100000]);

        return [$project, $proposal];
    });
}

test('the Al-Wadi showcase fills every section of the project and its proposal, and a second run adds nothing', function () {
    [$project, $proposal] = alWadiProject();

    $this->seed(AlWadiShowcaseSeeder::class);

    $counts = fn (): array => [
        'project milestones' => Milestone::query()->where('project_id', $project->id)->count(),
        'proposal milestones' => Milestone::query()->where('proposal_id', $proposal->id)->count(),
        'framework' => Result::query()->where('proposal_id', $proposal->id)->count(),
        'targets' => PlanIndicator::query()->where('source_type', 'proposal')->where('source_id', $proposal->id)->count(),
        'reports' => Report::query()->whereIn('plan_indicator_id', PlanIndicator::query()->where('source_id', $proposal->id)->select('id'))->count(),
        'tranches' => TransfersTracking::query()->count(),
        'tasks' => Task::query()->whereIn('source_type', ['project', 'proposal'])->count(),
        'risks' => $project->risks()->count(),
        'monthly reports' => $project->monthlyReports()->count(),
        'proposal files' => DB::table('media')->where('mediable_type', 'proposal')->where('mediable_id', $proposal->id)->count(),
        'status history' => DB::table('proposal_status_notes')->where('proposal_id', $proposal->id)->count(),
        'reviewers' => DB::table('proposal_reviewers')->where('proposal_id', $proposal->id)->count(),
    ];

    expect($first = $counts())->toBe([
        'project milestones' => 7,
        'proposal milestones' => 4,
        'framework' => 8,
        'targets' => 9,
        'reports' => 18,
        'tranches' => 3,
        'tasks' => 9,
        'risks' => 3,
        'monthly reports' => 6,
        'proposal files' => 5,
        'status history' => 7,
        'reviewers' => 4,
    ]);

    $this->seed(AlWadiShowcaseSeeder::class);

    expect($counts())->toBe($first);
});

test('the Al-Wadi showcase writes nothing when the project is missing', function () {
    $this->seed(AlWadiShowcaseSeeder::class);

    expect(Milestone::query()->count())->toBe(0)
        ->and(Result::query()->count())->toBe(0);
});
