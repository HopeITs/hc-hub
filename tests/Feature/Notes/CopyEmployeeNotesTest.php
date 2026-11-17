<?php

use App\Models\Note;
use App\Models\UserTodo;
use App\Support\Notes\CopyEmployeeNotes;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    $this->user = User::factory()->create(['employee_id' => $this->employee->id]);

    $this->legacy = fn (int $employeeId, string $type, string $content, array $extra = []) => DB::table('employee_notes')->insert([
        'employee_id' => $employeeId,
        'type' => $type,
        'content' => $content,
        'is_done' => false,
        'color' => '#58595b',
        'order' => 3,
        'created_at' => '2026-01-02 03:04:05',
        'updated_at' => '2026-02-03 04:05:06',
        ...$extra,
    ]);
});

test('notes become private personal notes and tasks become to-dos, dates kept', function () {
    ($this->legacy)($this->employee->id, 'note', 'A note');
    ($this->legacy)($this->employee->id, 'task', 'A done task', ['is_done' => true]);

    $result = (new CopyEmployeeNotes)();

    $note = Note::query()->sole();
    $todo = UserTodo::query()->sole();

    expect($result)->toBe(['notes' => 1, 'todos' => 1, 'skipped' => 0])
        ->and($note->author_id)->toBe($this->user->id)
        ->and($note->subject_type)->toBeNull()
        ->and($note->visibility->value)->toBe('private')
        ->and($note->color)->toBe('#58595b')
        ->and($note->created_at->toDateTimeString())->toBe('2026-01-02 03:04:05')
        ->and($note->updated_at->toDateTimeString())->toBe('2026-02-03 04:05:06')
        ->and($todo->user_id)->toBe($this->user->id)
        ->and($todo->title)->toBe('A done task')
        ->and($todo->is_done)->toBeTrue()
        ->and($todo->sort_order)->toBe(3)
        ->and($todo->created_at->toDateTimeString())->toBe('2026-01-02 03:04:05');
});

test('rows of an employee with no user are skipped and the old table is untouched', function () {
    $orphan = Employee::factory()->create();
    ($this->legacy)($orphan->id, 'note', 'Nobody owns this');

    expect((new CopyEmployeeNotes)()['skipped'])->toBe(1)
        ->and(Note::query()->count())->toBe(0)
        ->and(DB::table('employee_notes')->count())->toBe(1);
});

test('running the copy twice does not duplicate anything', function () {
    ($this->legacy)($this->employee->id, 'note', 'Once');
    ($this->legacy)($this->employee->id, 'task', 'Once too');

    (new CopyEmployeeNotes)();
    $second = (new CopyEmployeeNotes)();

    expect($second)->toBe(['notes' => 0, 'todos' => 0, 'skipped' => 2])
        ->and(Note::query()->count())->toBe(1)
        ->and(UserTodo::query()->count())->toBe(1);
});
