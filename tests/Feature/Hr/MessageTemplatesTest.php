<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\MessageTemplate;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\MessageTemplateRenderer;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| HR keeps email and WhatsApp templates; a profile offers them filled in for
| that person, as a new message to read and edit. Nothing is sent.
*/

beforeEach(function () {
    HrAccess::flush();

    $managerEmployee = Employee::factory()->create();
    $managerPosition = Position::factory()->create();
    Contract::factory()->for($managerEmployee)->for($managerPosition)->create(['is_active' => true]);
    User::factory()->create(['employee_id' => $managerEmployee->id, 'email' => 'mai@hcsyria.org']);

    $this->employee = Employee::factory()->create(['first_name_en' => 'Rami', 'work_phone' => '0944 123 456', 'whatsapp_work' => null]);
    Contract::factory()->for($this->employee)->for(Position::factory()->create(['direct_manager_id' => $managerPosition->id]))->create(['is_active' => true]);
    User::factory()->create(['employee_id' => $this->employee->id, 'email' => 'rami@hcsyria.org']);

    $this->renderer = app(MessageTemplateRenderer::class);
});

test('placeholders are filled and an unknown one is left as written', function () {
    expect($this->renderer->fill('Hi {first_name}, {unknown}', ['first_name' => 'Rami']))->toBe('Hi Rami, {unknown}');
});

test('a local Syrian number becomes international for WhatsApp', function () {
    expect($this->renderer->whatsappNumber($this->employee))->toBe('963944123456');

    $this->employee->whatsapp_work = '00963 955 000 111';

    expect($this->renderer->whatsappNumber($this->employee))->toBe('963955000111');
});

test('an email template opens a filled message with the manager on CC', function () {
    $template = MessageTemplate::factory()->create(['subject' => 'Welcome {first_name}', 'body' => 'Dear {first_name}', 'cc_manager' => true]);

    expect($this->renderer->url($template, $this->employee, null))
        ->toBe('mailto:rami@hcsyria.org?cc=mai%40hcsyria.org&subject=Welcome%20Rami&body=Dear%20Rami');

    $whatsapp = MessageTemplate::factory()->whatsapp()->create(['body' => 'Hi {first_name}']);

    expect($this->renderer->url($whatsapp, $this->employee, null))->toBe('https://wa.me/963944123456?text=Hi%20Rami');
});

test('only whoever manages templates may edit them', function () {
    $hr = User::factory()->create();
    $hr->givePermissionTo(Permission::findOrCreate('message-templates.manage', 'web'));

    $hr = $hr->fresh();

    Livewire::actingAs($hr)->test('hr::message-templates.index')
        ->call('openCreateModal')
        ->set('name', 'Welcome')
        ->set('channel', MessageTemplate::WHATSAPP)
        ->set('subject', 'Ignored')
        ->set('body', 'Welcome {first_name}')
        ->set('ccManager', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(MessageTemplate::query()->sole())
        ->subject->toBeNull()
        ->cc_manager->toBeFalse()
        ->created_by->toBe($hr->id);

    $this->actingAs(User::factory()->create()->fresh())->get(route('hr.message-templates.index'))->assertForbidden();
});

test('the profile offers the active templates to whoever may use them', function () {
    $template = MessageTemplate::factory()->create();
    $inactive = MessageTemplate::factory()->create(['is_active' => false]);

    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('message-templates.use', 'web'));

    $this->actingAs($user->fresh());

    $this->blade('<x-hr::employees.message-template-menu :employee="$employee" />', ['employee' => $this->employee])
        ->assertSee('data-message-template-link="'.$template->id.'"', false)
        ->assertDontSee('data-message-template-link="'.$inactive->id.'"', false);

    $this->actingAs(User::factory()->create()->fresh());

    $this->blade('<x-hr::employees.message-template-menu :employee="$employee" />', ['employee' => $this->employee])
        ->assertDontSee('data-message-template-menu', false);
});
