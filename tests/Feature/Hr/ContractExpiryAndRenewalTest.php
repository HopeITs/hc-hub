<?php

use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Console\DeactivateExpiredContractsCommand;
use Modules\Hr\Console\ProcessContractAutoRenewalsCommand;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;

/*
| The evening contract runs: `contracts:deactivate-expired` switches off a
| contract past its end date and frees its seat, then
| `contracts:process-auto-renewals` gives the ones that renew one successor.
*/

beforeEach(function () {
    $this->travelTo('2026-09-20 21:30:00');
    Notification::fake();

    // Type 1 is an annual contract (ContractType::CATEGORY_BY_ID); a trial never renews.
    $this->annual = ContractType::query()->find(1) ?? ContractType::factory()->create(['id' => 1]);
    $this->position = Position::factory()->create(['max_emp' => 1, 'exist_emp' => 1]);
    $this->contract = fn (array $attributes = []): Contract => Contract::factory()->create($attributes + [
        'position_id' => $this->position->id,
        'contract_type_id' => $this->annual->id,
        'type' => ContractTypeEnum::FullTime->value,
        'start_date' => '2026-01-01',
        'end_date' => '2026-09-15',
        'is_active' => true,
        'auto_renew' => false,
    ]);
});

test('a contract past its end date is deactivated and its seat is freed', function () {
    $expired = ($this->contract)();

    $this->artisan(DeactivateExpiredContractsCommand::class)->assertSuccessful();

    expect($expired->refresh()->is_active)->toBeFalse()
        ->and($this->position->refresh()->exist_emp)->toBe(0);

    $this->artisan(DeactivateExpiredContractsCommand::class)->assertSuccessful();

    expect($this->position->refresh()->exist_emp)->toBe(0);
});

test('a contract still running, or ending today, is left alone', function () {
    $running = ($this->contract)(['end_date' => '2026-12-31']);
    $endsToday = ($this->contract)(['end_date' => '2026-09-20']);
    $this->position->update(['exist_emp' => 2, 'max_emp' => 2]);

    $this->artisan(DeactivateExpiredContractsCommand::class)->assertSuccessful();

    expect($running->refresh()->is_active)->toBeTrue()
        ->and($endsToday->refresh()->is_active)->toBeTrue()
        ->and($this->position->refresh()->exist_emp)->toBe(2);
});

test('an ended draft goes inactive without freeing a seat it never held', function () {
    $draft = ($this->contract)(['type' => ContractTypeEnum::Draft->value]);

    $this->artisan(DeactivateExpiredContractsCommand::class)->assertSuccessful();

    expect($draft->refresh()->is_active)->toBeFalse()
        ->and($this->position->refresh()->exist_emp)->toBe(1);
});

test('an ended auto-renew contract gets exactly one successor, and a second run adds none', function () {
    $city = City::factory()->create();
    $hr = User::factory()->role('HR-'.strtoupper($city->slug))->create();
    $employee = Employee::factory()->create(['city_id' => $city->id]);
    $source = ($this->contract)(['employee_id' => $employee->id, 'auto_renew' => true]);
    $source->payTiers()->create(['condition_en' => 'Per session', 'condition_ar' => 'لكل جلسة', 'amount' => 25, 'sort_order' => 1]);

    $this->artisan(DeactivateExpiredContractsCommand::class)->assertSuccessful();
    $this->artisan(ProcessContractAutoRenewalsCommand::class)->assertSuccessful();

    $renewal = Contract::query()->where('employee_id', $employee->id)->whereKeyNot($source->id)->sole();

    expect($source->refresh()->is_active)->toBeFalse()
        ->and($renewal->is_active)->toBeTrue()
        ->and($renewal->auto_renew)->toBeTrue()
        ->and($renewal->start_date->toDateString())->toBe('2026-09-16')
        ->and($renewal->end_date->toDateString())->toBe('2026-12-31')
        ->and($renewal->position_id)->toBe($source->position_id)
        ->and($renewal->contract_type_id)->toBe($source->contract_type_id)
        ->and($renewal->payTiers()->pluck('condition_en')->all())->toBe(['Per session'])
        ->and($this->position->refresh()->exist_emp)->toBe(1);

    Notification::assertSentToTimes($hr, JourneyNotice::class, 1);
    Notification::assertSentTo($hr, JourneyNotice::class, fn (JourneyNotice $notice): bool => $notice->kind === 'contracts-auto-renewed'
        && $notice->params['names']['en'] === $employee->nameForLocale('en'));

    $this->artisan(ProcessContractAutoRenewalsCommand::class)->assertSuccessful();

    expect(Contract::query()->where('employee_id', $employee->id)->count())->toBe(2)
        ->and($this->position->refresh()->exist_emp)->toBe(1);
    Notification::assertSentToTimes($hr, JourneyNotice::class, 1);
});

test('auto-renewal skips a contract that is still active, switched off, terminated or a trial', function () {
    $trial = ContractType::query()->find(3) ?? ContractType::factory()->create(['id' => 3]);
    $this->position->update(['exist_emp' => 0, 'max_emp' => 5]);

    $stillActive = ($this->contract)(['auto_renew' => true]);
    $switchedOff = ($this->contract)(['is_active' => false, 'auto_renew' => false]);
    $terminated = ($this->contract)(['is_active' => false, 'auto_renew' => true, 'terminated_at' => '2026-09-01']);
    $onTrial = ($this->contract)(['is_active' => false, 'auto_renew' => true, 'contract_type_id' => $trial->id]);

    $this->artisan(ProcessContractAutoRenewalsCommand::class)->assertSuccessful();

    expect(Contract::query()->count())->toBe(4)
        ->and($this->position->refresh()->exist_emp)->toBe(0)
        ->and($stillActive->refresh()->is_active)->toBeTrue()
        ->and($switchedOff->refresh()->is_active)->toBeFalse()
        ->and($terminated->refresh()->is_active)->toBeFalse()
        ->and($onTrial->refresh()->is_active)->toBeFalse();
});
