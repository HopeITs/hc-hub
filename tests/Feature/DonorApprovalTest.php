<?php

use App\Models\Activity;
use App\Models\UserNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Actions\Donors\ApproveDonor;
use Modules\Tracking\Actions\Donors\DeclineDonor;
use Modules\Tracking\Actions\Donors\SaveDonor;
use Modules\Tracking\Enums\DonorApprovalStatus;
use Modules\Tracking\Models\Donor;
use Modules\Tracking\Models\Proposal;

/**
 * The donor form's validated input for SaveDonor.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function donorFormInput(array $overrides = []): array
{
    return [
        'name' => 'Nordic Relief Fund',
        'donorType' => '',
        'contactPerson' => '',
        'email' => '',
        'phone' => '',
        'country' => '',
        'notes' => '',
        'lastContactedOn' => '',
        'nextStep' => '',
        'nextStepDueOn' => '',
        ...$overrides,
    ];
}

test('a donor recorded by someone who cannot approve is a pending request with its requester', function () {
    $requester = userWithRole('Public Relations Manager');
    $this->actingAs($requester);

    $donor = app(SaveDonor::class)->handle(null, donorFormInput(), $requester);

    expect($donor->approval_status)->toBe(DonorApprovalStatus::Pending)
        ->and($donor->requested_by)->toBe($requester->id)
        ->and($donor->approved_by)->toBeNull()
        ->and($donor->approved_at)->toBeNull()
        ->and(Activity::query()->where('event', 'donor.requested')->count())->toBe(1);
});

test('a donor recorded by the General Manager is approved at once', function () {
    $manager = userWithRole('General Manager');
    $this->actingAs($manager);

    $donor = app(SaveDonor::class)->handle(null, donorFormInput(), $manager);

    expect($donor->approval_status)->toBe(DonorApprovalStatus::Approved)
        ->and($donor->approved_by)->toBe($manager->id)
        ->and($donor->approved_at)->not->toBeNull();
});

test('editing a pending donor never changes its status', function () {
    $requester = userWithRole('Public Relations Manager');
    $this->actingAs($requester);
    $donor = app(SaveDonor::class)->handle(null, donorFormInput(), $requester);

    app(SaveDonor::class)->handle($donor, donorFormInput(['name' => 'Nordic Relief Fund II']), userWithRole('General Manager'));

    expect($donor->fresh()->approval_status)->toBe(DonorApprovalStatus::Pending)
        ->and($donor->fresh()->name)->toBe('Nordic Relief Fund II');
});

test('the General Manager approves a pending donor and the requester is told', function () {
    $requester = userWithRole('Public Relations');
    $manager = userWithRole('General Manager');
    $donor = Donor::factory()->pending($requester)->create();

    $this->actingAs($manager);
    $approved = app(ApproveDonor::class)->handle($donor, $manager);

    expect($approved->approval_status)->toBe(DonorApprovalStatus::Approved)
        ->and($approved->approved_by)->toBe($manager->id)
        ->and($approved->approved_at)->not->toBeNull()
        ->and(UserNotification::query()->forUser($requester)->get()->pluck('data.key')->all())->toContain('notifications.events.donor.approved');
});

test('approving needs donors.approve', function (string $role) {
    $donor = Donor::factory()->pending(userWithRole('Public Relations'))->create();

    $this->actingAs($actor = userWithRole($role));

    expect(fn () => app(ApproveDonor::class)->handle($donor, $actor))->toThrow(AuthorizationException::class)
        ->and($donor->fresh()->approval_status)->toBe(DonorApprovalStatus::Pending);
})->with(['PR Manager' => 'Public Relations Manager', 'Financial' => 'Financial', 'Team Member' => 'Team Member']);

test('declining needs donors.approve', function () {
    $donor = Donor::factory()->pending(userWithRole('Public Relations'))->create();

    $this->actingAs($actor = userWithRole('Public Relations Manager'));

    expect(fn () => app(DeclineDonor::class)->handle($donor, 'We already fund this donor elsewhere.', $actor))->toThrow(AuthorizationException::class)
        ->and(Donor::query()->whereKey($donor->id)->exists())->toBeTrue();
});

test('a second approval is refused by the pending guard', function () {
    $manager = userWithRole('General Manager');
    $donor = Donor::factory()->pending(userWithRole('Public Relations'))->create();
    $this->actingAs($manager);

    app(ApproveDonor::class)->handle($donor, $manager);

    expect(fn () => app(ApproveDonor::class)->handle($donor, $manager))->toThrow(ValidationException::class);
});

test('declining with a short reason is refused', function () {
    $manager = userWithRole('General Manager');
    $donor = Donor::factory()->pending(userWithRole('Public Relations'))->create();
    $this->actingAs($manager);

    expect(fn () => app(DeclineDonor::class)->handle($donor, 'No.', $manager))->toThrow(ValidationException::class)
        ->and(Donor::query()->whereKey($donor->id)->exists())->toBeTrue();
});

test('declining an unreferenced donor removes the request and tells the requester', function () {
    $requester = userWithRole('Public Relations');
    $manager = userWithRole('General Manager');
    $donor = Donor::factory()->pending($requester)->create();
    $this->actingAs($manager);

    app(DeclineDonor::class)->handle($donor, 'We already fund this donor elsewhere.', $manager);

    expect(Donor::query()->whereKey($donor->id)->exists())->toBeFalse()
        ->and(UserNotification::query()->forUser($requester)->get()->pluck('data.key')->all())->toContain('notifications.events.donor.declined');
});

test('declining a donor a proposal already uses keeps it pending with the reason in its notes', function () {
    $requester = userWithRole('Public Relations');
    $manager = userWithRole('General Manager');
    $donor = Donor::factory()->pending($requester)->create(['notes' => 'Met at the Geneva forum.']);
    Proposal::factory()->create(['donor_id' => $donor->id]);
    $this->actingAs($manager);

    app(DeclineDonor::class)->handle($donor, 'The legal name is still unconfirmed.', $manager);

    $donor->refresh();

    expect($donor->approval_status)->toBe(DonorApprovalStatus::Pending)
        ->and($donor->notes)->toContain('Met at the Geneva forum.')
        ->and($donor->notes)->toContain('The legal name is still unconfirmed.')
        ->and(UserNotification::query()->forUser($requester)->get()->pluck('data.key')->all())->toContain('notifications.events.donor.declined');
});

test('a donor request reaches the General Manager', function () {
    $manager = userWithRole('General Manager');
    $requester = userWithRole('Public Relations Manager');
    $this->actingAs($requester);

    app(SaveDonor::class)->handle(null, donorFormInput(), $requester);

    expect(UserNotification::query()->forUser($manager)->get()->pluck('data.key')->all())->toContain('notifications.events.donor.requested');
});

test('existing donors are approved', function () {
    expect(Donor::factory()->create()->fresh()->approval_status)->toBe(DonorApprovalStatus::Approved);
});
