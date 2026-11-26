<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
| The employee form matches HR (7.10c): nationality is optional, the Contact
| tab offers a login to an active employee, a new record's first save offers
| to hold a position with a draft contract, and editing stays on the page.
*/

beforeEach(function () {
    $this->city = City::factory()->create();
    $this->hr = User::factory()->create();
    $this->hr->givePermissionTo(Permission::findOrCreate('employees.create', 'web'), Permission::findOrCreate('employees.update', 'web'));
});

/**
 * @return array<string, string>
 */
function parityFormFields(City $city, array $overrides = []): array
{
    return array_merge([
        'first_name_ar' => 'رنا', 'first_name_en' => 'Rana',
        'last_name_ar' => 'حداد', 'last_name_en' => 'Haddad',
        'father_name_ar' => 'سامي', 'father_name_en' => 'Sami',
        'mother_name_ar' => 'ليلى', 'mother_name_en' => 'Layla',
        'username' => 'rana.haddad',
        'city_id' => (string) $city->id,
        'national_number' => 'X-12345',
        'gender' => '2',
        'marital_status' => '1',
    ], $overrides);
}

function fillParityForm($component, array $fields)
{
    foreach ($fields as $field => $value) {
        $component->set('form.'.$field, $value);
    }

    return $component;
}

function completeEmployee(City $city, array $state = []): Employee
{
    return Employee::factory()->create(array_merge([
        'username' => 'omar.k',
        'city_id' => $city->id,
        'national_number' => 'N-998877',
        'father_name_ar' => 'أ', 'father_name_en' => 'A',
        'mother_name_ar' => 'ب', 'mother_name_en' => 'B',
        'gender' => 1,
        'marital_status' => 1,
        'is_active' => true,
    ], $state));
}

test('a new record has no nationality and completes without one', function () {
    $component = Livewire::actingAs($this->hr)->test('hr::employees.form')->assertSet('form.nationality', '');

    fillParityForm($component, parityFormFields($this->city))->call('save')->assertHasNoErrors();

    expect(Employee::query()->where('username', 'rana.haddad')->sole()->nationality)->toBeNull();
});

test('the login checkbox is hidden for a draft and offered to an active employee', function () {
    $draft = completeEmployee($this->city, ['username' => 'draft.one', 'national_number' => 'D-1']);
    $draft->forceFill(['record_status' => Employee::RECORD_DRAFT])->save();

    Livewire::actingAs($this->hr)->test('hr::employees.form', ['employee' => $draft])
        ->assertDontSeeHtml('data-test="create-account"')
        ->assertSeeHtml('data-test="account-at-contract"');

    Livewire::actingAs($this->hr)->test('hr::employees.form', ['employee' => completeEmployee($this->city)])
        ->assertSeeHtml('data-test="create-account"')
        ->assertSet('createAccount', false);
});

test('ticking create login account makes username@hcsyria.org with the employee role', function () {
    Event::fake(['hr.employee.account-created']);
    Role::findOrCreate('employee', 'web');
    $employee = completeEmployee($this->city);

    Livewire::actingAs($this->hr)->test('hr::employees.form', ['employee' => $employee])
        ->set('createAccount', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $user = User::query()->where('employee_id', $employee->id)->sole();

    expect($user->email)->toBe('omar.k@hcsyria.org')
        ->and($user->hasRole('employee'))->toBeTrue();
    Event::assertDispatched('hr.employee.account-created');
});

test('a work email used by another login blocks the save on the contact tab', function () {
    User::factory()->create(['email' => 'omar.k@hcsyria.org']);
    $employee = completeEmployee($this->city);

    Livewire::actingAs($this->hr)->test('hr::employees.form', ['employee' => $employee])
        ->set('createAccount', true)
        ->call('save')
        ->assertHasErrors('form.username')
        ->assertSet('activeTab', 'contact');

    expect(User::query()->where('employee_id', $employee->id)->exists())->toBeFalse();
});

test('an employee with a login sees it ticked, and the login follows a new username', function () {
    $employee = completeEmployee($this->city);
    $user = User::factory()->create(['email' => 'omar.k@hcsyria.org']);
    $user->forceFill(['employee_id' => $employee->id])->save();

    Livewire::actingAs($this->hr)->test('hr::employees.form', ['employee' => $employee])
        ->assertSet('createAccount', true)
        ->set('form.username', 'omar.khalil')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->email)->toBe('omar.khalil@hcsyria.org');
});

test('the first save of a new record offers a draft contract and opens the record', function () {
    ContractType::factory()->create();
    $program = Program::factory()->unassigned()->create(['is_active' => true]);
    $position = Position::factory()->create(['city_id' => $this->city->id, 'program_id' => $program->id, 'is_active' => true]);

    $component = fillParityForm(Livewire::actingAs($this->hr)->test('hr::employees.form'), parityFormFields($this->city))
        ->call('save')
        ->assertNoRedirect()
        ->assertSet('showDraftPrompt', true)
        ->assertSet('draftCityId', (string) $this->city->id)
        ->assertSeeHtml('data-test="draft-prompt"');

    $employee = Employee::query()->where('username', 'rana.haddad')->sole();

    $component->set('draftProgramId', (string) $program->id)
        ->set('draftPositionId', (string) $position->id)
        ->call('createDraftAndContinue')
        ->assertHasNoErrors()
        ->assertRedirect(route('hr.employees.edit', $employee->id));

    expect(Contract::query()->where('employee_id', $employee->id)->sole())
        ->type->toBe(ContractTypeEnum::Draft->value)
        ->position_id->toBe($position->id);
});

test('skipping the draft contract opens the record without one', function () {
    $component = fillParityForm(Livewire::actingAs($this->hr)->test('hr::employees.form'), parityFormFields($this->city))
        ->call('saveAsDraft')
        ->assertSet('showDraftPrompt', true);

    $employee = Employee::query()->where('username', 'rana.haddad')->sole();

    $component->call('skipDraft')->assertRedirect(route('hr.employees.edit', $employee->id));

    expect(Contract::query()->where('employee_id', $employee->id)->exists())->toBeFalse();
});

test('the draft contract step cannot run before the record is saved', function () {
    $this->withoutVite();

    Livewire::actingAs($this->hr)->test('hr::employees.form')
        ->call('createDraftAndContinue')
        ->assertForbidden();
});

test('saving an existing record stays on the form', function () {
    Livewire::actingAs($this->hr)->test('hr::employees.form', ['employee' => completeEmployee($this->city)])
        ->set('form.first_name_en', 'Omar')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSet('showDraftPrompt', false);
});

test('an error on the contact tab opens that tab, one on the profile opens the profile', function () {
    $component = fillParityForm(Livewire::actingAs($this->hr)->test('hr::employees.form'), parityFormFields($this->city, ['personal_email' => 'not-an-email']))
        ->call('save')
        ->assertHasErrors('form.personal_email')
        ->assertSet('activeTab', 'contact');

    $component->set('form.first_name_en', '')
        ->call('save')
        ->assertSet('activeTab', 'information');
});

test('a new record shows the status checklist, locked sections and HR button order', function () {
    Livewire::actingAs($this->hr)->test('hr::employees.form')
        ->assertSeeHtml('data-test="record-status"')
        ->assertSeeHtml('data-test="check-username"')
        ->assertSeeHtml('data-test="check-marital_status"')
        ->assertSeeHtml('data-test="sections-locked"')
        ->assertSeeHtmlInOrder(['data-test="save-employee"', 'data-test="save-draft"', 'data-test="cancel"'])
        ->assertDontSeeHtml('data-test="offers-awaiting-conversion"');
});

test('a checklist item jumps to the tab that holds it', function () {
    Livewire::actingAs($this->hr)->test('hr::employees.form')
        ->set('activeTab', 'contact')
        ->assertSet('activeTab', 'contact')
        ->set('activeTab', 'nowhere')
        ->assertSet('activeTab', 'information');
});

test('a complete record links to its other sections and hides the checklist', function () {
    Livewire::actingAs($this->hr)->test('hr::employees.form', ['employee' => completeEmployee($this->city)])
        ->assertDontSeeHtml('data-test="record-status"')
        ->assertSeeHtml('data-test="section-records"')
        ->assertSeeHtml('data-test="section-contracts"');
});

test('the photo upload still refuses other files and large images', function (UploadedFile $file) {
    Livewire::actingAs($this->hr)->test('hr::employees.form', ['employee' => completeEmployee($this->city)])
        ->set('photo', $file)
        ->call('save')
        ->assertHasErrors('photo');
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
    'oversized image' => fn () => UploadedFile::fake()->image('big.jpg')->size(5000),
]);
