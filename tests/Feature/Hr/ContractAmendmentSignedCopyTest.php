<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;
use Spatie\Permission\Models\Permission;

/*
| The signed copy of a contract amendment: HR who may amend the contract
| attaches it on the amendments page (an hr_media row, collection `signed`,
| on the private side of the HR disk); whoever may print the sheet downloads
| it. It carries the pay clause, so nobody else reaches it.
*/

/**
 * An HR account for every governorate holding the given permissions.
 */
function hrUserWith(string ...$permissions): User
{
    $user = User::factory()->role('HR-SYR')->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission));
    }

    return $user->fresh();
}

/**
 * @return list<stdClass>
 */
function signedCopiesOf(ContractAmendment $amendment): array
{
    return DB::table('hr_media')
        ->where('model_type', 'App\Models\ContractAmendment')
        ->where('model_id', $amendment->id)
        ->where('collection_name', 'signed')
        ->orderBy('id')
        ->get()
        ->all();
}

beforeEach(function () {
    Storage::fake('hr');

    $this->contract = Contract::factory()->create(['is_active' => true]);
    $this->amendment = ContractAmendment::factory()->approved()->for($this->contract)->create();
});

test('HR who may amend the contract attaches the signed copy and downloads it', function () {
    $hr = hrUserWith('contracts.view', 'contracts.amend', 'contracts.export');

    Livewire::actingAs($hr)
        ->test('hr::contract-amendments.index', ['contract' => $this->contract])
        ->assertSee(__('hr_contract_amendments.actions.attach_signed'))
        ->assertDontSee(__('hr_contract_amendments.actions.download_signed'))
        ->set('signedFiles.'.$this->amendment->id, validUpload('signed-sheet.pdf'))
        ->assertHasNoErrors()
        ->assertSee(__('hr_contract_amendments.actions.replace_signed'))
        ->assertSeeHtml(route('hr.contracts.amendments.signed', [$this->contract->id, $this->amendment->id]));

    $copies = signedCopiesOf($this->amendment);

    expect($copies)->toHaveCount(1)
        ->and($copies[0]->disk)->toBe('local')
        ->and($copies[0]->name)->toBe('amendment-'.$this->contract->id.'-1');

    Storage::disk('hr')->assertExists('private/'.$copies[0]->id.'/'.$copies[0]->file_name);

    $response = $this->actingAs($hr)
        ->get(route('hr.contracts.amendments.signed', [$this->contract->id, $this->amendment->id]))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Content-Disposition'))->toContain('attachment')->toContain('amendment-'.$this->contract->id.'-1.pdf')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->streamedContent())->toStartWith('%PDF');
});

test('a new signed copy replaces the one on file', function () {
    $hr = hrUserWith('contracts.view', 'contracts.amend', 'contracts.export');
    $page = Livewire::actingAs($hr)->test('hr::contract-amendments.index', ['contract' => $this->contract]);

    $page->set('signedFiles.'.$this->amendment->id, validUpload('first.pdf'));
    $first = signedCopiesOf($this->amendment)[0];

    $page->set('signedFiles.'.$this->amendment->id, validUpload('second.png'))->assertHasNoErrors();
    $copies = signedCopiesOf($this->amendment);

    expect($copies)->toHaveCount(1)
        ->and($copies[0]->id)->not->toBe($first->id)
        ->and($copies[0]->file_name)->toEndWith('.png');

    Storage::disk('hr')->assertMissing('private/'.$first->id.'/'.$first->file_name);
});

test('someone who may not amend the contract cannot attach a signed copy', function () {
    $viewer = hrUserWith('contracts.view');

    Livewire::actingAs($viewer)
        ->test('hr::contract-amendments.index', ['contract' => $this->contract])
        ->assertDontSee(__('hr_contract_amendments.actions.attach_signed'))
        ->set('signedFiles.'.$this->amendment->id, validUpload('signed-sheet.pdf'))
        ->assertForbidden();

    expect(signedCopiesOf($this->amendment))->toBe([]);
});

test('the signed copy is refused to anyone who may not print the sheet', function (array $permissions, string $role) {
    $hr = hrUserWith('contracts.view', 'contracts.amend', 'contracts.export');
    Livewire::actingAs($hr)
        ->test('hr::contract-amendments.index', ['contract' => $this->contract])
        ->set('signedFiles.'.$this->amendment->id, validUpload('signed-sheet.pdf'));

    $other = User::factory()->role($role)->create()->fresh();
    foreach ($permissions as $permission) {
        $other->givePermissionTo(Permission::findOrCreate($permission));
    }

    $this->actingAs($other)
        ->get(route('hr.contracts.amendments.signed', [$this->contract->id, $this->amendment->id]))
        ->assertForbidden();
})->with([
    'HR without the export permission' => [['contracts.view', 'contracts.amend'], 'HR-SYR'],
    'a role that does not read pay' => [['contracts.view', 'contracts.export'], 'Team Member'],
]);

test('the signed copy route answers 404 when nothing is on file or the amendment belongs to another contract', function () {
    $hr = hrUserWith('contracts.view', 'contracts.amend', 'contracts.export');
    $otherContract = Contract::factory()->create(['is_active' => true]);

    $this->actingAs($hr)
        ->get(route('hr.contracts.amendments.signed', [$this->contract->id, $this->amendment->id]))
        ->assertNotFound();

    $this->actingAs($hr)
        ->get(route('hr.contracts.amendments.signed', [$otherContract->id, $this->amendment->id]))
        ->assertNotFound();
});

test('a file that is not a PDF or an image is rejected', function (UploadedFile $file) {
    $hr = hrUserWith('contracts.view', 'contracts.amend', 'contracts.export');

    Livewire::actingAs($hr)
        ->test('hr::contract-amendments.index', ['contract' => $this->contract])
        ->set('signedFiles.'.$this->amendment->id, $file)
        ->assertHasErrors('signedFiles.'.$this->amendment->id);

    expect(signedCopiesOf($this->amendment))->toBe([]);
})->with([
    'a Word document' => [fn () => validUpload('signed-sheet.docx')],
    'a script' => [fn () => UploadedFile::fake()->createWithContent('signed-sheet.php', '<?php echo 1;')],
    'a script renamed to pdf' => [fn () => UploadedFile::fake()->createWithContent('signed-sheet.pdf', '<?php echo 1;')],
]);
