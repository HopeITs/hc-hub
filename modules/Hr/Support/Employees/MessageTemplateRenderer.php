<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Employees;

use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\MessageTemplate;
use Modules\Hr\Support\Leave\LeaveRequestPricing;

/**
 * Fills a MessageTemplate in for one person and builds the link that opens
 * it as a new message (mailto: or wa.me). Nothing is sent from the server:
 * the user reads and edits the message first.
 *
 * What a variable may reveal follows what the viewer may already see. Name,
 * position, city and manager are what every colleague sees on a profile.
 * The contract and leave variables resolve only for a contract the viewer's
 * city scope covers (Contract::visibleTo) and are empty otherwise. Pay is
 * never a variable.
 */
final class MessageTemplateRenderer
{
    /** The placeholders a template may use, for the editor's help text. */
    public const VARIABLES = [
        'name', 'first_name', 'position', 'city', 'manager_name',
        'contract_end_date', 'contract_number', 'leave_days',
        'hr_phone', 'sender_name', 'today',
    ];

    public function __construct(private readonly LeaveRequestPricing $pricing) {}

    /**
     * @return array<string, string>
     */
    public function variables(Employee $employee, ?User $viewer): array
    {
        $contract = $this->contractFor($employee, $viewer);
        $position = $contract?->position;
        $manager = $position?->direct_manager_id === null ? null : Employee::query()
            ->select(Employee::NameColumns)
            ->where('is_active', true)
            ->whereHas('contracts', fn ($contracts) => $contracts->activeNow()->where('position_id', $position->direct_manager_id))
            ->first();
        $viewer?->loadMissing('employee:'.implode(',', Employee::NameColumns));
        $cityId = $employee->city_id === null ? null : (int) $employee->city_id;

        return [
            'name' => (string) $employee->name,
            'first_name' => (string) $employee->first_name,
            'position' => (string) ($position?->title ?? ''),
            'city' => City::nameOf($cityId),
            'manager_name' => (string) ($manager?->name ?? ''),
            'contract_end_date' => $contract?->end_date?->format('d-m-Y') ?? '',
            'contract_number' => $contract === null ? '' : (string) $contract->id,
            'leave_days' => $contract === null ? '' : $this->annualLeaveDays($contract),
            'hr_phone' => (string) ($cityId === null ? '' : City::query()->whereKey($cityId)->value('hr_hotline')),
            'sender_name' => (string) ($viewer?->employee?->name ?? ''),
            'today' => now()->format('d-m-Y'),
        ];
    }

    /**
     * Replace every known {placeholder}; an unknown one is left as written.
     *
     * @param  array<string, string>  $variables
     */
    public function fill(string $text, array $variables): string
    {
        return (string) preg_replace_callback(
            '/\{([a-z_]+)\}/',
            fn (array $match): string => array_key_exists($match[1], $variables) ? $variables[$match[1]] : $match[0],
            $text,
        );
    }

    /**
     * The link that opens this template for this person, or null when the
     * person has no address for its channel.
     */
    public function url(MessageTemplate $template, Employee $employee, ?User $viewer): ?string
    {
        $variables = $this->variables($employee, $viewer);
        $body = $this->fill($template->body, $variables);

        if ($template->isWhatsapp()) {
            $number = $this->whatsappNumber($employee);

            return $number === null ? null : 'https://wa.me/'.$number.'?text='.rawurlencode($body);
        }

        $employee->loadMissing('account:id,employee_id,email');
        $to = $employee->work_email;

        if (! filled($to) || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $query = [];

        if ($template->cc_manager) {
            $cc = Employee::directManagerEmails([$employee])[(int) $employee->id] ?? null;

            if ($cc !== null && strcasecmp($cc, $to) !== 0 && filter_var($cc, FILTER_VALIDATE_EMAIL) !== false) {
                $query[] = 'cc='.rawurlencode($cc);
            }
        }

        $query[] = 'subject='.rawurlencode($this->fill((string) $template->subject, $variables));
        $query[] = 'body='.rawurlencode(str_replace("\n", "\r\n", $body));

        return 'mailto:'.$to.'?'.implode('&', $query);
    }

    /**
     * Digits for wa.me, from the work WhatsApp number or, failing that, the
     * work phone. A local Syrian number (09…) becomes international (963 9…).
     */
    public function whatsappNumber(Employee $employee): ?string
    {
        $raw = filled($employee->whatsapp_work) ? $employee->whatsapp_work : $employee->work_phone;
        $digits = (string) preg_replace('/\D+/', '', (string) $raw);

        if ($digits === '') {
            return null;
        }

        return match (true) {
            str_starts_with($digits, '00') => substr($digits, 2),
            str_starts_with($digits, '0') => '963'.substr($digits, 1),
            default => $digits,
        };
    }

    private function contractFor(Employee $employee, ?User $viewer): ?Contract
    {
        if ($viewer === null) {
            return null;
        }

        return Contract::query()
            ->visibleTo($viewer)
            ->activeNow()
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->where('employee_id', $employee->id)
            ->with(['position.center', 'employee'])
            ->latest('start_date')
            ->latest('id')
            ->first();
    }

    /** Annual leave left, in whole or half days, or '' when it cannot be told. */
    private function annualLeaveDays(Contract $contract): string
    {
        $balance = $contract->leaveBalances()->get()->first(fn (LeaveBalance $balance): bool => $balance->isAnnual());
        $balance?->setRelation('contract', $contract);
        $perDay = $this->pricing->hoursPerDayFor($contract);

        if ($balance === null || $perDay === null || $perDay <= 0) {
            return '';
        }

        $days = max(0, $this->pricing->availableHoursFor($balance)) / $perDay;

        return rtrim(rtrim(number_format($days, 1, '.', ''), '0'), '.') ?: '0';
    }
}
