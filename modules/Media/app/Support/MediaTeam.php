<?php

namespace Modules\Media\Support;

use App\Models\City;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Media\Models\MediaRequest;

/**
 * Who is who for media requests, read from HR's contracts and positions.
 *
 *  - Media team      an active contract on a position of the Communication
 *                    department (config media-requests.media_department_codes)
 *  - Media manager   a team member whose position's job-level grade is in
 *                    media-requests.media_manager_grades
 *  - Program manager an active contract on a position of that program whose
 *                    grade is in media-requests.program_manager_grades
 *                    (programs are country-wide, so the city does not matter)
 *
 * Specialties come from the position title ("Communication team member /
 * Photographer"), part-time hours from the contract's working hours.
 * Results are memoized for the lifetime of the request (scoped binding).
 */
class MediaTeam
{
    /** @var array<string, mixed> */
    private array $memo = [];

    /** The HR employee behind an account (User::profile() is untyped, so callers go through here). */
    public static function employeeOf(?User $user): ?Employee
    {
        $employee = $user?->profile;

        return $employee instanceof Employee ? $employee : null;
    }

    public function isAdministrator(?User $user): bool
    {
        return $user !== null && in_array($user->myRole(), config('access-control.administrator_roles', []), true);
    }

    /** super-admin may stand in for the media manager (setup, absences). */
    public function canActAsMediaManager(?User $user): bool
    {
        return $user !== null && ($user->myRole() === 'super-admin' || $this->isManager(self::employeeOf($user)));
    }

    /**
     * Someone who only asks for work: not on the media team, approves for no
     * program, and is not an administrator. They get the home screen only;
     * their requests and notifications open inside it.
     */
    public function isRequesterOnly(?User $user): bool
    {
        return $user !== null
            && ! $this->isMember(self::employeeOf($user))
            && ! $this->canActAsMediaManager($user)
            && $this->managedProgramIds(self::employeeOf($user)) === []
            && ! $this->isAdministrator($user);
    }

    /** The field a request belongs to (design, photo, video, montage), from its service and type. */
    public function specialty(?string $service, ?string $type): ?string
    {
        foreach (config('media-requests.specialties', []) as $key => $s) {
            $types = $s['for'][$service] ?? null;
            if ($types === '*' || (is_array($types) && in_array($type, $types, true))) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The member's specialties, read from their position title.
     *
     * @return array<int, string>
     */
    public function skillsOf(?Employee $employee): array
    {
        return $this->skillsFromTitle($this->titleOf($employee));
    }

    /**
     * Specialties named in a position title: "Communication team member / Photographer"
     * gives photo. Parts marked "(on need)" are left out: see extraSkillsOf.
     *
     * @return array<int, string>
     */
    public function skillsFromTitle(string $title): array
    {
        return $this->match('specialties', $this->titleParts($title, false));
    }

    /**
     * Specialties this person does when needed: "Photographer (on need)".
     *
     * @return array<int, string>
     */
    public function extraSkillsOf(?Employee $employee): array
    {
        return $this->extraSkillsFromTitle($this->titleOf($employee));
    }

    /** @return array<int, string> */
    public function extraSkillsFromTitle(string $title): array
    {
        return array_values(array_diff($this->match('specialties', $this->titleParts($title, true)), $this->skillsFromTitle($title)));
    }

    /**
     * What else they do (interviews, print, laser cut, animation, …), from config media-requests.extras.
     *
     * @return array<int, string>
     */
    public function extrasOf(?Employee $employee): array
    {
        return $this->extrasFromTitle($this->titleOf($employee));
    }

    /** @return array<int, string> */
    public function extrasFromTitle(string $title): array
    {
        return $this->match('extras', array_merge($this->titleParts($title, false), $this->titleParts($title, true)));
    }

    public function hasSkill(?Employee $employee, ?string $specialty): bool
    {
        return $specialty !== null && in_array($specialty, $this->skillsOf($employee), true);
    }

    /** 0 their field, 1 they do it on need, 2 not theirs. */
    public function skillRank(?Employee $employee, ?string $specialty): int
    {
        return $this->hasSkill($employee, $specialty) ? 0 : ($specialty !== null && in_array($specialty, $this->extraSkillsOf($employee), true) ? 1 : 2);
    }

    /** The title of the member's media position (HR position title), empty when they have none. */
    public function titleOf(?Employee $employee): string
    {
        return (string) $this->mediaPositionOf($employee)?->title_en;
    }

    /**
     * Part-time hours as "HH:MM-HH:MM", from the media contract's working hours;
     * null for the team's own day (full time).
     */
    public function workHoursOf(?Employee $employee): ?string
    {
        $contract = $this->mediaContract($employee) ?? $this->anyContract($employee);
        if ($contract === null || ! $contract->work_hours_from || ! $contract->work_hours_to) {
            return null;
        }
        $hours = substr((string) $contract->work_hours_from, 0, 5).'-'.substr((string) $contract->work_hours_to, 0, 5);
        $team = config('media-requests.work.from', '09:00').'-'.config('media-requests.work.to', '17:00');

        return $hours === $team ? null : $hours;
    }

    /** Part of a working day this person works: 1 full time, 0.5 for four hours. */
    public function dayShare(?Employee $employee): float
    {
        if (! preg_match('/^(\d{1,2})(?::(\d{2}))?-(\d{1,2})(?::(\d{2}))?$/', (string) $this->workHoursOf($employee), $m)) {
            return 1.0;
        }
        $hours = ((int) $m[3] + (int) ($m[4] ?? 0) / 60) - ((int) $m[1] + (int) $m[2] / 60);

        return $hours > 0 ? min(1.0, round($hours / 8, 2)) : 1.0;
    }

    /**
     * This person's working hours as minutes after midnight: their own (part time) or the team's day.
     *
     * @return array{0: int, 1: int}
     */
    public function workMinutes(?Employee $employee): array
    {
        $team = config('media-requests.work.from', '09:00').'-'.config('media-requests.work.to', '17:00');

        foreach ([(string) $this->workHoursOf($employee), $team] as $hours) {
            if (preg_match('/^(\d{1,2})(?::(\d{2}))?-(\d{1,2})(?::(\d{2}))?$/', $hours, $m)) {
                [$from, $to] = [(int) $m[1] * 60 + (int) $m[2], (int) $m[3] * 60 + (int) ($m[4] ?? 0)];
                if ($to > $from) {
                    return [$from, $to];
                }
            }
        }

        return [9 * 60, 17 * 60];
    }

    /**
     * Team members for a request's field: its specialists first, then everyone else.
     * For a shoot, the team of the place comes first (see coverage).
     *
     * @return Collection<int, Employee>
     */
    public function membersFor(?string $specialty, ?MediaRequest $r = null): Collection
    {
        $area = $r !== null && $this->onSite($r) ? $this->areaTeams($r) : null;

        return $this->members()->sortBy(fn (Employee $e) => [
            $area === null ? 0 : $this->areaRank($e, $area),
            $this->skillRank($e, $specialty),
        ])->values();
    }

    /** A photo or video shoot: someone has to be there, so the team of the place does it. */
    public function onSite(MediaRequest $r): bool
    {
        return in_array($r->type, config('media-requests.coverage.on_site.'.$r->service, []), true);
    }

    /**
     * The governorates whose team covers the request's place, nearest first.
     *
     * @return array<int, string>
     */
    public function areaTeams(MediaRequest $r): array
    {
        $slug = $r->city?->slug;

        return $slug === null ? [] : config('media-requests.coverage.served_by.'.$slug, [$slug]);
    }

    /** Covered through a service agreement (Al-Jazira), not by our own team. */
    public function underAgreement(MediaRequest $r): bool
    {
        return $this->onSite($r) && in_array($r->city?->slug, config('media-requests.coverage.agreement', []), true);
    }

    /** Whether the member's own team covers the request; anyone does for work that is not a shoot. */
    public function coversArea(?Employee $employee, MediaRequest $r): bool
    {
        return ! $this->onSite($r) || ($employee !== null && $this->areaRank($employee, $this->areaTeams($r)) < 99);
    }

    /** An open (source) file, e.g. .ai or .psd: for the media team's archive only. */
    public static function isOpenFile(?string $fileName): bool
    {
        return in_array(strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION)), config('media-requests.open_files', []), true);
    }

    /** The media team (and whoever stands in for the media manager) sees open files and the archive. */
    public function seesArchive(?User $user): bool
    {
        return $user !== null && ($this->isMember(self::employeeOf($user)) || $this->canActAsMediaManager($user));
    }

    public function isMember(?Employee $employee): bool
    {
        return $employee !== null && $employee->is_active && $this->mediaContract($employee) !== null;
    }

    public function isManager(?Employee $employee): bool
    {
        $level = $this->mediaPositionOf($employee)?->getRelationValue('jobLevel');

        return $employee !== null && $employee->is_active && in_array(
            $level instanceof JobLevel ? $level->grade : null,
            config('media-requests.media_manager_grades', []),
            false,
        );
    }

    /** @return array<int, int> */
    public function managedProgramIds(?Employee $employee): array
    {
        if ($employee === null) {
            return [];
        }

        return $this->memo['pm-of:'.$employee->id] ??= $this->programManagerPosition(Position::query())
            ->whereIn('id', Contract::query()->activeNow()->where('employee_id', $employee->id)->select('position_id'))
            ->pluck('program_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()->values()->all();
    }

    public function managesProgram(?Employee $employee, ?int $programId): bool
    {
        return $programId !== null && in_array($programId, $this->managedProgramIds($employee), true);
    }

    /** @return Collection<int, Employee> */
    public function programManagers(?Program $program): Collection
    {
        if ($program === null) {
            return collect();
        }

        return $this->memo['pm:'.$program->id] ??= $this->withAccounts(Employee::query()
            ->where('is_active', true)
            ->whereIn('id', $this->holdersOf($this->programManagerPosition(Position::query())->where('program_id', $program->id)))
            ->orderBy('first_name_en')->get());
    }

    /** @return Collection<int, Employee> */
    public function managers(): Collection
    {
        return $this->memo['managers'] ??= $this->members()->filter(fn (Employee $e): bool => $this->isManager($e))->values();
    }

    /** @return Collection<int, Employee> */
    public function members(): Collection
    {
        return $this->memo['members'] ??= $this->withAccounts(Employee::query()
            ->where('is_active', true)
            ->whereIn('id', $this->holdersOf($this->mediaPosition(Position::query())))
            ->with('city')->orderBy('first_name_en')->get());
    }

    /**
     * @template TEmployee of Employee|null
     *
     * @param  Collection<int, TEmployee>  $employees
     * @return Collection<int, User>
     */
    public function accounts(Collection $employees): Collection
    {
        return $employees
            ->filter(fn (?Employee $e): bool => $e !== null && $e->is_active !== false)
            ->map(fn (Employee $e): ?User => ($account = $e->getRelationValue('account')) instanceof User ? $account : null)
            ->filter()
            ->unique('id')
            ->values();
    }

    /** @return array<int, string> */
    public function departmentCodes(): array
    {
        return array_values(array_filter((array) config('media-requests.media_department_codes', ['Comm'])));
    }

    public function flush(): void
    {
        $this->memo = [];
    }

    /** Today in the team's time zone, for callers that compare contract dates. */
    public function today(): Carbon
    {
        return now((string) config('media-requests.work.timezone', 'Asia/Damascus'))->startOfDay();
    }

    /**
     * The parts of a title, either the main ones or the "(on need)" ones.
     *
     * @return array<int, string>
     */
    private function titleParts(string $title, bool $onNeed): array
    {
        $parts = preg_split('/\s*[\/,،|]\s*/u', trim($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $need = '/\(?\s*(on\s*need|when\s*needed|if\s*needed|عند\s*الحاجة)\s*\)?/iu';

        return array_values(array_filter($parts, fn (string $p): bool => (preg_match($need, $p) === 1) === $onNeed));
    }

    /**
     * @param  array<int, string>  $parts
     * @return array<int, string>
     */
    private function match(string $list, array $parts): array
    {
        $text = implode(' / ', $parts);

        return $text === '' ? [] : array_keys(array_filter(
            config('media-requests.'.$list, []),
            fn (array $s): bool => isset($s['title']) && preg_match('/'.$s['title'].'/iu', $text) === 1,
        ));
    }

    /** @param  array<int, string>  $area */
    private function areaRank(Employee $e, array $area): int
    {
        $city = $e->city;
        $i = array_search($city instanceof City ? $city->slug : null, $area, true);

        return $i === false ? 99 : $i;
    }

    /**
     * The employee's active contract on a media-department position (the most senior
     * one when they hold several), or null when they are not on the team.
     */
    private function mediaContract(?Employee $employee): ?Contract
    {
        if ($employee?->id === null) {
            return null;
        }

        $key = 'mc:'.$employee->id;
        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $employee->contracts()
                ->whereHas('position', fn (Builder $p) => $this->mediaPosition($p))
                ->with('position.jobLevel')
                ->get()
                ->sortBy(fn (Contract $c) => $c->position?->jobLevel->grade ?? PHP_INT_MAX)
                ->first();
        }

        return $this->memo[$key];
    }

    /**
     * Attach each employee's account in one query (HR's account() relation is
     * untyped, so it cannot be eager-loaded by name under static analysis).
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Employee>  $employees
     * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
     */
    private function withAccounts(\Illuminate\Database\Eloquent\Collection $employees): \Illuminate\Database\Eloquent\Collection
    {
        $users = User::query()->whereIn('employee_id', $employees->modelKeys())->get()->keyBy('employee_id');

        return $employees->each(fn (Employee $e) => $e->setRelation('account', $users->get($e->id)));
    }

    /** The position of the member's media contract. */
    private function mediaPositionOf(?Employee $employee): ?Position
    {
        $position = $this->mediaContract($employee)?->getRelationValue('position');

        return $position instanceof Position ? $position : null;
    }

    /**
     * Employee ids holding an active contract on one of these positions.
     *
     * @param  Builder<Position>  $positions
     * @return Builder<Contract>
     */
    private function holdersOf(Builder $positions): Builder
    {
        return Contract::query()->activeNow()->whereIn('position_id', $positions->select('id'))->select('employee_id');
    }

    private function anyContract(?Employee $employee): ?Contract
    {
        if ($employee?->id === null) {
            return null;
        }
        $key = 'ac:'.$employee->id;
        if (! array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $employee->contracts()->latest('start_date')->first();
        }

        return $this->memo[$key];
    }

    /**
     * @param  Builder<Position>  $position
     * @return Builder<Position>
     */
    private function mediaPosition(Builder $position): Builder
    {
        return $position->where('is_active', true)
            ->whereHas('department', fn (Builder $d) => $d->whereIn('code', $this->departmentCodes()));
    }

    /**
     * @param  Builder<Position>  $position
     * @return Builder<Position>
     */
    private function programManagerPosition(Builder $position): Builder
    {
        return $position->where('is_active', true)->whereNotNull('program_id')
            ->whereHas('jobLevel', fn (Builder $j) => $j->whereIn('grade', config('media-requests.program_manager_grades', [1])));
    }
}
