<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

use Illuminate\Notifications\Notification;

/**
 * The kinds of notification a person can tune, one per thing the app really
 * sends (app/Notifications). There is no attendance or payroll module, so
 * there is no category for them: add one here when such a notification exists.
 */
enum NotificationCategory: string
{
    case LeaveActions = 'leave_actions';
    case LeaveUpdates = 'leave_updates';
    case Contracts = 'contracts';
    case Employees = 'employees';
    case Checklists = 'checklists';
    case Files = 'files';
    case Organization = 'organization';
    case Security = 'security';
    case System = 'system';

    /**
     * The category of each HR notification, by class basename (the
     * notification classes themselves move into the module with the HR
     * screens).
     *
     * @var array<string, self>
     */
    private const array MAP = [
        'NewLeaveRequest' => self::LeaveActions,
        'LeaveDecisionReminder' => self::LeaveActions,
        'ActionLeaveRequest' => self::LeaveUpdates,
        'HRSawLeaveRequest' => self::LeaveUpdates,
        'LeaveRequestObjected' => self::LeaveUpdates,
        'ActionLeaveBalance' => self::LeaveUpdates,
        'ContractsAutoRenewedSummary' => self::Contracts,
        'ContractsExpiringAutoRenewWarning' => self::Contracts,
        'NewEmployeeWelcome' => self::Employees,
        'ProfileChangeDecided' => self::Employees,
        'ChecklistAssigned' => self::Checklists,
        'FolderShared' => self::Files,
        'NewCenterCreated' => self::Organization,
        'CenterClosed' => self::Organization,
        'DigitalIdReportedLost' => self::Security,
        'TestNotification' => self::System,
    ];

    public static function forNotification(Notification $notification): self
    {
        return self::MAP[class_basename($notification)] ?? self::System;
    }

    public function label(): string
    {
        return __('notification-settings.categories.'.$this->value.'.label');
    }

    public function description(): string
    {
        return __('notification-settings.categories.'.$this->value.'.description');
    }

    public function icon(): string
    {
        return match ($this) {
            self::LeaveActions, self::LeaveUpdates => 'calendar',
            self::Contracts => 'file',
            self::Employees => 'users',
            self::Checklists => 'check',
            self::Files => 'folder',
            self::Organization => 'building',
            self::Security => 'shield',
            self::System => 'bell',
        };
    }

    /**
     * Urgent notifications may pass quiet hours (when the person allows it)
     * and reach "urgent only" devices.
     */
    public function urgent(): bool
    {
        return $this === self::Security;
    }

    /**
     * The starting matrix for someone who never changed it. Email stays off
     * except where the app already mailed before preferences existed
     * (the two contract summaries), so nobody's inbox changes overnight.
     *
     * @return array{in_app: bool, desktop: bool, mobile: bool, email: bool}
     */
    public function defaults(): array
    {
        return [
            'in_app' => true,
            'desktop' => true,
            'mobile' => $this !== self::Organization,
            'email' => $this === self::Contracts || $this === self::Security,
        ];
    }
}
