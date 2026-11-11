<?php

use App\Support\Desk\Cards\DonorReportsCard;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Support\Desk\Cards\BadgesCard;
use Modules\Hr\Support\Desk\Cards\ChecklistCard;
use Modules\Hr\Support\Desk\Cards\PendingRequestsCard;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\DonorReport;

/*
| A Desk card's figures count every record, not the RowLimit (60) rows its
| list holds, and a card reads them in as few queries as it can.
*/

/**
 * How many queries the callback runs.
 */
function deskCardQueries(Closure $read): int
{
    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });
    $read();

    return $queries;
}

test('the donor reports card counts past its list, and draws figures, breakdown and hero from one query', function () {
    $this->travelTo('2026-11-11 09:00:00');
    $agreement = DonorProposal::factory()->create();
    DonorReport::factory()->count(61)->for($agreement)->create(['due_on' => '2026-10-01']);
    DonorReport::factory()->for($agreement)->create(['due_on' => '2026-11-11']);
    DonorReport::factory()->for($agreement)->create(['due_on' => '2026-11-20']);
    DonorReport::factory()->for($agreement)->create(['due_on' => '2027-03-01']);
    DonorReport::factory()->count(2)->for($agreement)->submitted()->create(['due_on' => '2026-09-01']);
    $user = User::factory()->role('Super Admin')->create()->load('roles');
    $card = new DonorReportsCard;

    $queries = deskCardQueries(function () use ($card, $user, &$figures, &$parts, &$hero): void {
        $figures = $card->figures($user);
        $parts = $card->breakdown($user);
        $hero = $card->hero($user);
    });

    expect($queries)->toBe(1)
        ->and(array_column($figures, 'value'))->toBe(['61', '2', '2'])
        ->and(array_column($parts, 'count'))->toBe([61, 3, 2])
        ->and($hero['big'])->toBe('61')
        ->and(array_column($hero['items'], 'count'))->toBe([1, 1, 0, 0, 0, 0, 0, 0]);
});

test('the badges card counts every reward and warning, not only the rows it lists', function () {
    $employeeId = insertHrEmployee();
    $user = User::factory()->create(['employee_id' => $employeeId]);
    $badge = fn (BadgeType $type): array => ['employee_id' => $employeeId, 'type' => (string) $type->value, 'date' => '2026-01-01 00:00:00', 'created_at' => now(), 'updated_at' => now()];
    Badge::query()->insert([...array_fill(0, 61, $badge(BadgeType::Reward)), ...array_fill(0, 3, $badge(BadgeType::Warning)), $badge(BadgeType::Salary_Increase)]);

    expect(array_column((new BadgesCard)->figures($user), 'value'))->toBe(['61', '3']);
});

test('the checklist and pending requests cards count every item, not only the rows they list', function () {
    $employeeId = insertHrEmployee();
    $colleagueId = insertHrEmployee(firstNameEn: 'Lina');
    $user = User::factory()->create(['employee_id' => $employeeId]);
    $item = fn (int $holder, int $deliverer, string $status): array => ['employee_id' => $holder, 'delivered_id' => $deliverer, 'delivered_status' => $status, 'created_at' => now(), 'updated_at' => now()];
    Checklist::query()->insert([
        ...array_fill(0, 62, $item($employeeId, $colleagueId, 'delivered')),
        ...array_fill(0, 63, $item($colleagueId, $employeeId, 'pending')),
        $item($employeeId, $colleagueId, 'pending'),
    ]);

    expect((new ChecklistCard)->figures($user)[0]['value'])->toBe('62')
        ->and((new PendingRequestsCard)->figures($user)[0]['value'])->toBe('63');
});
