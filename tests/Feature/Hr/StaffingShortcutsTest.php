<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| Shortcuts from the staffing pages: a position with a free seat opens the
| hiring request with the position pre-filled (positions list and team
| chart), for whoever may write hiring requests; the team chart writes to
| the team's shared mailbox.
*/

beforeEach(function () {
    HrAccess::flush();

    $this->viewer = User::factory()->create();
    $this->viewer->givePermissionTo(Permission::findOrCreate('positions.view', 'web'));

    $this->hirer = User::factory()->create();

    foreach (['positions.view', 'recruitment-requests.view', 'recruitment-requests.manage'] as $permission) {
        $this->hirer->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    $this->viewer = $this->viewer->fresh();
    $this->hirer = $this->hirer->fresh();

    $this->open = Position::factory()->create(['max_emp' => 2, 'exist_emp' => 1, 'is_active' => true]);
    $this->full = Position::factory()->create(['max_emp' => 1, 'exist_emp' => 1, 'is_active' => true]);
    $this->requestUrl = fn (Position $position): string => e(route('hr.recruitment.requests.create', ['type' => RecruitmentRequestType::FillVacancy->value, 'position_id' => $position->id]));
});

test('a position with a free seat opens the hiring request, for whoever may write one', function () {
    Livewire::actingAs($this->hirer)->test('hr::positions.index')
        ->assertSeeHtml('data-open-vacancy="'.$this->open->id.'"')
        ->assertSeeHtml(($this->requestUrl)($this->open))
        ->assertDontSeeHtml('data-open-vacancy="'.$this->full->id.'"');

    Livewire::actingAs($this->viewer)->test('hr::positions.index')
        ->assertDontSeeHtml('data-open-vacancy=');
});

test('the team chart asks for a hire on an open seat and emails the team mailbox', function () {
    config(['app.team_email' => 'team@hcsyria.org']);

    Livewire::actingAs($this->hirer)->test('hr::positions.team')
        ->assertSeeHtml('data-request-hire="'.$this->open->id.'"')
        ->assertSeeHtml(($this->requestUrl)($this->open))
        ->assertDontSeeHtml('data-request-hire="'.$this->full->id.'"')
        ->assertSeeHtml('href="mailto:team@hcsyria.org"');

    Livewire::actingAs($this->viewer)->test('hr::positions.team')
        ->assertDontSeeHtml('data-request-hire=');
});

test('without a team mailbox there is no email the team', function () {
    config(['app.team_email' => null]);

    Livewire::actingAs($this->viewer)->test('hr::positions.team')
        ->assertDontSeeHtml('data-test="email-team"');
});
