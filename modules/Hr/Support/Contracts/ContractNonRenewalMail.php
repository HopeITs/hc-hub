<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Contracts;

use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\RenewalJourneys;

/**
 * "Don't renew": the mailto: link a manager uses to ask HR not to renew one
 * of their team's contracts. It opens a new message (nothing is sent from the
 * server), addressed to the HR of the employee's city (HR-SYR when the city
 * has none), with the sender's own direct manager on CC, a fixed subject and
 * a message that already says what is needed. HR decides in the renewal
 * journey as before.
 *
 * Reads names, position, city and dates only. Pay is never loaded.
 */
final class ContractNonRenewalMail
{
    private const POSITION = 'position:id,title_en,title_ar,city_id,direct_manager_id';

    public function __construct(
        private readonly RenewalJourneys $journeys,
        private readonly DeliveryLedger $ledger,
    ) {}

    /**
     * May $viewer ask for this contract not to be renewed: it still renews
     * by itself, has not ended, and $viewer holds the position its position
     * reports to.
     */
    public function isOfferedTo(Contract $contract, ?User $viewer): bool
    {
        if ($viewer === null || ! $contract->auto_renew || ! $contract->is_active || $contract->terminated_at !== null
            || $contract->end_date === null || $contract->end_date->lt(today())) {
            return false;
        }

        // The same columns url() reads, so a contract checked here can be passed on.
        $contract->loadMissing(self::POSITION);

        return $this->journeys->managerAccount($contract)?->is($viewer) ?? false;
    }

    /**
     * The mailto: URL, or null when no HR address can be found (the caller
     * then shows no link rather than a message to nobody).
     */
    public function url(Contract $contract, ?User $sender = null): ?string
    {
        $contract->loadMissing([
            'employee:'.implode(',', [...Employee::NameColumns, 'city_id']),
            self::POSITION,
        ]);

        $employee = $contract->employee;

        if ($employee === null) {
            return null;
        }

        $cityId = $employee->city_id ?? $contract->position?->city_id;
        $to = $this->ledger->cityHr($cityId === null ? null : (int) $cityId)
            ->pluck('email')
            ->filter(fn (mixed $email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();

        if ($to === []) {
            return null;
        }

        // CC: the direct manager of whoever presses the button (they are the
        // employee's manager, so their own manager is told), never one of the To.
        $cc = $sender?->employee_id === null ? null : (Employee::directManagerEmails([(int) $sender->employee_id])[(int) $sender->employee_id] ?? null);
        $cc = filled($cc) && filter_var($cc, FILTER_VALIDATE_EMAIL) !== false && ! in_array(strtolower((string) $cc), array_map('strtolower', $to), true)
            ? (string) $cc
            : null;

        $sender?->loadMissing('employee:'.implode(',', Employee::NameColumns));

        $name = (string) $employee->name;
        $position = (string) $contract->position?->title;

        $subject = __('hr_journeys.dont_renew.subject', ['name' => $name, 'position' => $position]);
        $body = __('hr_journeys.dont_renew.body', [
            'name' => $name,
            'position' => $position,
            'city' => City::nameOf($cityId === null ? null : (int) $cityId),
            'end' => $contract->end_date?->format('d-m-Y') ?? '',
            'number' => (string) $contract->id,
            'sender' => (string) ($sender?->employee?->name ?? ''),
        ]);

        // The addresses stay literal; everything after the ? is encoded.
        $query = array_filter([
            'cc' => $cc === null ? null : rawurlencode($cc),
            'subject' => rawurlencode($subject),
            'body' => rawurlencode(str_replace("\n", "\r\n", $body)),
        ]);

        return 'mailto:'.implode(',', $to).'?'.collect($query)->map(fn (string $value, string $key): string => $key.'='.$value)->implode('&');
    }
}
