<?php

namespace Modules\Media\Database\Seeders;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Media\Support\MediaTeam;

/**
 * Sample people for media requests, as HR rows: an employee, a login with an
 * active role, and the contracts that make MediaTeam see them —
 *
 *   admin  super-admin
 *   rami   asks for work (no media contract, manages no program)
 *   lina   Program Manager of DISC and MP (job level grade 1)
 *   maya   media manager (Communication department, grade 5)
 *   fadi   media team, designer
 *   sami   media team, videographer / editor
 *
 * Password "password", emails <key>@example.test. Used by the module's tests
 * and for a local preview; never run in production. Programs and governorates
 * are the HR ones (created here only when missing, e.g. on the test database).
 */
class MediaDemoSeeder extends Seeder
{
    public const PROGRAMS = [
        ['001', 'HC', 'Christian Hope Center'], ['002', 'MP', 'Micro Projects'], ['003', 'RLF', 'Relief'],
        ['004', 'S2S', 'School to School'], ['005', 'DISC', 'Development Impact and Support Center'],
        ['006', 'SZ', 'Study Zone'], ['008', 'CU', 'Children University'], ['009', 'CYG', 'CYG'], ['010', 'LEAP', 'LEAP'], ['011', 'ITP', 'ITP'],
        ['000', 'PARTNER', 'Partners and donors'],
    ];

    public const CITIES = [
        ['000', 'SYR', 'Syria', 'سوريا'], ['001', 'ALP', 'Aleppo', 'حلب'], ['002', 'HMS', 'Homs', 'حمص'],
        ['003', 'DMS', 'Damascus', 'دمشق'], ['004', 'LAT', 'Latakia', 'اللاذقية'], ['005', 'TAR', 'Tartus', 'طرطوس'],
        ['006', 'HAM', 'Hama', 'حماة'], ['007', 'DAR', 'Daraa', 'درعا'], ['008', 'SWD', 'Suwayda', 'السويداء'],
        ['009', 'QNT', 'Quneitra', 'القنيطرة'], ['010', 'RQA', 'Raqqa', 'الرقة'], ['011', 'DZR', 'Deir ez-Zor', 'دير الزور'],
        ['012', 'HSK', 'Hasakah', 'الحسكة'], ['013', 'IDL', 'Idlib', 'إدلب'], ['014', 'RID', 'Damascus Countryside', 'ريف دمشق'],
    ];

    public function run(): void
    {
        // Sample logins with the password "password": never on a server.
        if (app()->environment('production')) {
            return;
        }

        self::places();

        foreach ([
            'admin' => ['Admin', 'المسؤول', 'super-admin', null, [], null],
            'rami' => ['Rami', 'رامي', 'employee', null, [], null],
            'lina' => ['Lina', 'لينا', 'manager', null, ['DISC', 'MP'], null],
            'maya' => ['Maya', 'مايا', 'manager', 'manager', [], 'Communication manager / Designer / Photographer'],
            'fadi' => ['Fadi', 'فادي', 'employee', 'member', [], 'Communication team member / Designer'],
            'sami' => ['Sami', 'سامي', 'employee', 'member', [], 'Communication team member / Videographer / Editor'],
        ] as $key => [$en, $ar, $role, $team, $programs, $title]) {
            self::person($key, $en, $ar, role: $role, team: $team, title: $title, programs: $programs);
        }
    }

    public static function places(): void
    {
        foreach (self::PROGRAMS as [$code, $slug, $name]) {
            Program::query()->firstOrCreate(['slug' => $slug], ['code' => $code, 'name' => $name, 'is_active' => true]);
        }
        foreach (self::CITIES as [$code, $slug, $en, $ar]) {
            City::query()->firstOrCreate(['slug' => $slug], ['code' => $code, 'name_en' => $en, 'name_ar' => $ar, 'is_active' => true]);
        }
    }

    /**
     * One person: employee, login (<key>@example.test, active $role) and contracts.
     *
     * @param  'member'|'manager'|null  $team  on the Media team (Communication department), as manager or not
     * @param  string|null  $title  their media position title (specialties are read from it)
     * @param  array<int, string>  $programs  slugs of the programs they are Program Manager of
     * @param  string|null  $hours  part-time hours, e.g. "10:00-14:00"
     */
    public static function person(
        string $key,
        string $en,
        ?string $ar = null,
        string $role = 'employee',
        ?string $team = null,
        ?string $title = null,
        array $programs = [],
        string $city = 'ALP',
        ?string $hours = null,
        bool $account = true,
    ): Employee {
        $cityId = City::query()->where('slug', $city)->value('id');
        $employee = Employee::query()->where('username', $key)->first() ?? Employee::query()->create([
            'username' => $key,
            'first_name_en' => $en,
            'first_name_ar' => $ar ?? $en,
            'last_name_en' => '',
            'last_name_ar' => '',
            'city_id' => $cityId,
            'is_active' => true,
        ]);

        if ($team !== null) {
            self::contract($employee, self::position(
                Department::query()->firstOrCreate(['code' => 'Comm'], ['name' => 'Communication']),
                self::jobLevel($team === 'manager' ? 5 : 6),
                Program::query()->where('slug', 'HC')->value('id'),
                $cityId,
                $title ?? 'Communication team member',
            ), $hours);
        }
        foreach ($programs as $slug) {
            self::contract($employee, self::position(
                Department::query()->firstOrCreate(['code' => 'MGMT'], ['name' => 'Management']),
                self::jobLevel(1),
                Program::query()->where('slug', $slug)->value('id'),
                $cityId,
                'Program Manager',
            ), $hours);
        }

        if ($account && ! User::query()->where('employee_id', $employee->id)->exists()) {
            self::account($employee, $key.'@example.test', $role);
        }

        app(MediaTeam::class)->flush();

        return $employee;
    }

    /** A login for the employee whose active role is $role. */
    public static function account(Employee $employee, string $email, string $role = 'employee'): User
    {
        $user = User::query()->create(['email' => $email, 'password' => 'password', 'employee_id' => $employee->id]);
        $roleModel = Role::findOrCreate($role, 'web');
        $user->assignRole($roleModel);
        $user->roles()->updateExistingPivot($roleModel->id, ['is_active' => true]);

        return $user->fresh() ?? $user;
    }

    public static function jobLevel(int $grade): JobLevel
    {
        return JobLevel::query()->firstOrCreate(['grade' => $grade], [
            'name_en' => $grade === 1 ? 'Program Manager' : ($grade === 5 ? 'Department Manager' : 'Team'),
            'name_ar' => $grade === 1 ? 'مدير البرنامج' : ($grade === 5 ? 'مدير القسم' : 'الفريق'),
        ]);
    }

    public static function position(Department $department, JobLevel $level, ?int $programId, ?int $cityId, string $title): Position
    {
        $center = Center::query()->firstOrCreate(['city_id' => $cityId, 'name_en' => 'Hope Center'], [
            'name_ar' => 'مركز الرجاء', 'weekends' => 'friday', 'open_at' => '08:00:00', 'close_at' => '16:00:00', 'is_active' => true,
        ]);

        return Position::query()->forceCreate([
            'center_id' => $center->id,
            'city_id' => $cityId,
            'department_id' => $department->id,
            'job_level_id' => $level->id,
            'program_id' => $programId,
            'title_en' => $title,
            'title_ar' => $title,
            'max_emp' => 5,
            'exist_emp' => 0,
            'is_active' => true,
        ]);
    }

    public static function contract(Employee $employee, Position $position, ?string $hours): Contract
    {
        [$from, $to] = $hours !== null ? explode('-', $hours) : ['09:00', '17:00'];

        return Contract::query()->create([
            'employee_id' => $employee->id,
            'position_id' => $position->id,
            'contract_type_id' => ContractType::query()->firstOrCreate(['name_en' => 'Employment Contract'], ['name_ar' => 'عقد عمل'])->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'work_hours_from' => $from.':00',
            'work_hours_to' => $to.':00',
            'weekly_hours' => 40,
            'is_active' => true,
        ]);
    }
}
