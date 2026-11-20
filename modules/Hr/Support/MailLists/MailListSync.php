<?php

declare(strict_types=1);

namespace Modules\Hr\Support\MailLists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\MailListMember;
use Modules\Hr\Models\Position;

/**
 * Compares a mailing list with the people who should be on it according to HR data.
 *
 * The system never changes the group at the mail provider. It reports, and the only write it makes
 * is to queue an addition as pending for the person who holds the control panel (queueMissing).
 *
 * A list's rule (MailList::rule_type):
 *  - all_staff: everyone with a contract running today;
 *  - custom: people on a contract running today whose position matches every filter set on the list
 *    (city, project, department) and, when asked, who lead a team;
 *  - board: nobody is expected from HR data (the board is not an employee group), so only the
 *    members who left the organisation are reported.
 *
 * Only people with an account (and so a work address) can be compared by address.
 */
class MailListSync
{
    /**
     * @return array{
     *     missing: Collection<int, array{email: string, name: string}>,
     *     not_matching: Collection<int, array{id: int, email: string, name: string}>,
     *     left: Collection<int, array{id: int, email: string, name: string}>,
     *     members: int,
     *     in_sync: bool
     * }
     */
    public function compare(MailList $list): array
    {
        $expected = $this->expected($list);
        $members = $list->members()->get();
        $byEmail = $members->keyBy(fn (MailListMember $member): string => Str::lower($member->email));

        $accounts = User::query()
            ->whereIn('email', $byEmail->keys()->all())
            ->with('employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar,is_active')
            ->get()
            ->keyBy(fn (User $user): string => Str::lower($user->email));

        $missing = $expected
            ->reject(fn (string $name, string $email): bool => $byEmail->has($email))
            ->map(fn (string $name, string $email): array => ['email' => $email, 'name' => $name])
            ->values();

        $left = collect();
        $notMatching = collect();

        foreach ($byEmail as $email => $member) {
            $employee = $accounts->get($email)?->employee;

            if ($employee === null) {
                continue; // an outside address: nothing in HR data to compare with
            }

            $row = ['id' => (int) $member->id, 'email' => (string) $email, 'name' => (string) $employee->name];

            if (! $employee->is_active) {
                $left->push($row);
            } elseif ($list->rule_type !== MailList::RULE_BOARD && ! $expected->has($email)) {
                $notMatching->push($row);
            }
        }

        return [
            'missing' => $missing->sortBy('name')->values(),
            'not_matching' => $notMatching->sortBy('name')->values(),
            'left' => $left->sortBy('name')->values(),
            'members' => $members->count(),
            'in_sync' => $missing->isEmpty() && $notMatching->isEmpty() && $left->isEmpty(),
        ];
    }

    /**
     * The work address (lower case) => name of everyone the list's rule says belongs on it.
     *
     * @return Collection<string, string>
     */
    public function expected(MailList $list): Collection
    {
        if ($list->rule_type === null || $list->rule_type === MailList::RULE_BOARD) {
            return collect();
        }

        $contracts = Contract::query()
            ->activeNow()
            ->whereHas('employee.account')
            ->with('employee.account');

        if ($list->rule_type === MailList::RULE_CUSTOM) {
            $contracts->whereHas('position', function (Builder $position) use ($list): void {
                /** @var Builder<Position> $position */
                if ($list->rule_city_id) {
                    $position->inCity($list->rule_city_id);
                }

                if ($list->rule_program_id) {
                    $position->where('positions.program_id', $list->rule_program_id);
                }

                if ($list->rule_department_id) {
                    $position->where('positions.department_id', $list->rule_department_id);
                }

                if ($list->rule_managers_only) {
                    $position->managersOnly('direct');
                }
            });
        }

        return $contracts->get()
            ->map(fn (Contract $contract): ?Employee => $contract->employee)
            ->filter(fn (?Employee $employee): bool => filled($employee?->account?->email))
            ->mapWithKeys(fn (Employee $employee): array => [Str::lower((string) $employee->account?->email) => (string) $employee->name]);
    }

    /**
     * Queue every missing person on the list as pending, for the control panel's holder to add there.
     *
     * @return int how many were queued
     */
    public function queueMissing(MailList $list, ?int $byUserId = null): int
    {
        $count = 0;

        foreach ($this->compare($list)['missing'] as $person) {
            $list->members()->firstOrCreate(
                ['email' => $person['email']],
                ['status' => MailListMember::PENDING, 'added_by_user_id' => $byUserId],
            );
            $count++;
        }

        return $count;
    }

    /** Queue one person (who must be among those the rule expects). */
    public function queueOne(MailList $list, string $email, ?int $byUserId = null): bool
    {
        $email = Str::lower($email);

        if (! $this->expected($list)->has($email)) {
            return false;
        }

        $list->members()->firstOrCreate(
            ['email' => $email],
            ['status' => MailListMember::PENDING, 'added_by_user_id' => $byUserId],
        );

        return true;
    }
}
