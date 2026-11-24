<?php

declare(strict_types=1);

namespace Modules\Core\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\RecordsActivity;
use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use App\Models\Media;
use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Models\TrustedDevice;
use App\Models\UserNotification;
use App\Models\UserTodo;
use App\Notifications\ActivityRecorder;
use App\Notifications\TracksActivity;
use App\Support\EmployeeLookup;
use App\Support\EmployeeProfile;
use App\Support\Like;
use App\Support\Slots;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Modules\Core\Models\Concerns\RemembersLookup;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasRoles;

/**
 * One account of the hub, shared by every module (Core).
 *
 * People log in with users.email and users.password (AuthenticateUser);
 * ids are HR's user ids (hub:import-hr). `employee_id` links the HR
 * employee, whose is_active decides whether the account may still sign in
 * (EnsureAccountIsActive). `name` is the employee's English name, or the
 * email for an account with no employee, read from a remembered lookup so a
 * relation loaded as `user:id` still shows it.
 *
 * @property int $id
 * @property-read string $name
 * @property string $email
 * @property string $password
 * @property int|null $employee_id
 * @property bool $is_system an account with no person behind it, which never loses access
 * @property int|null $active_role_id
 * @property string|null $locale
 * @property list<string>|null $nav_pins the sidebar pages this person pinned, as route names
 * @property list<array{route: string, key: string, label: string, kind: string, n?: int, t?: int}>|null $nav_recent the records this person works in, with how often (n) and when last (t) each was opened; read through RemembersRecentRecord::mostUsed()
 * @property array<mixed>|null $desk_layout the saved Desk arrangement, list<array{panel: string, size: string}> as written; read through Panels::layoutFor(), which drops anything malformed
 * @property array<mixed>|null $page_panels the Panels popup's sets per route name, array<string, list<array{panel: string, size: string}>> as written; read through Panels::pageLayoutFor()
 * @property array<mixed>|null $desk_panel_flags the Desk panels pinned to the top and folded into tiles, array{pinned?: list<string>, folded?: list<string>} as written; read through Panels::flagsFor()
 * @property array<mixed>|null $desk_quick_access the Desk's Quick access list, Ask row keys in order (list<string>) as written; read through QuickAccess::for(), which drops what is no longer offered
 * @property bool $notification_popups
 * @property CarbonImmutable|null $tour_seen_at
 * @property CarbonImmutable|null $email_verified_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property bool $email_two_factor_enabled
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * The modules register their relations on a user (resolveRelationUsing), so
 * Core depends on neither: HrServiceProvider adds employee(),
 * TrackingServiceProvider createdTasks(), reports() and indicatorProgress().
 * Tracking's duties (owned projects, donor reports) are in TrackingDuties.
 */
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, PasskeyUser, TracksActivity
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, RecordsActivity, RemembersLookup, TwoFactorAuthenticatable;

    use HasRoles {
        checkPermissionTo as protected roleGrantsPermissionTo;
    }

    /**
     * Whether the user holds a permission, as every `can()` and `@can` asks.
     *
     * The abilities under `access-control.meal_manager_only` (building the
     * logframe and its targets) are honoured only for the holder of the MEAL
     * Manager slot or a bypass role, whatever the user's roles grant. The
     * abilities under `access-control.meal_manager_grants` (the proposal page,
     * its Funding tab included) are added for the MEAL Manager slot holder.
     *
     * @param  string|int|Permission|\BackedEnum  $permission
     */
    public function checkPermissionTo($permission, ?string $guardName = null): bool
    {
        if (is_string($permission)
            && in_array($permission, config('access-control.meal_manager_only.abilities'), true)
            && ! $this->buildsTheLogframe()) {
            return false;
        }

        if ($this->roleGrantsPermissionTo($permission, $guardName)) {
            return true;
        }

        return is_string($permission)
            && in_array($permission, config('access-control.meal_manager_grants'), true)
            && $this->holdsMealManagerSlot();
    }

    /**
     * Whether this user's HR employee holds the MEAL Manager slot right now.
     */
    public function holdsMealManagerSlot(): bool
    {
        return $this->employee_id !== null && (int) $this->employee_id === Slots::holderOf('meal_manager');
    }

    /**
     * Whether the user's roles grant a permission, leaving the MEAL manager
     * rule aside: for the places that only ask "is this person MEAL?" (a MEAL
     * reviewer attaching a need assessment), not "may they build the logframe".
     */
    public function roleGrants(string $permission): bool
    {
        return $this->roleGrantsPermissionTo($permission);
    }

    /**
     * Whether this user may build the logframe at all: the MEAL Manager slot
     * holder, or a bypass role (Super Admin, General Manager).
     */
    public function buildsTheLogframe(): bool
    {
        if ($this->hasAnyRole(config('access-control.meal_manager_only.bypass_roles'))) {
            return true;
        }

        return $this->holdsMealManagerSlot();
    }

    /**
     * Mirror the columns' DB defaults so a user that has not been round-tripped
     * through the database still reports them under strict mode: no language
     * chosen, no linked HR employee, no desktop notification popups and no
     * emailed sign-in codes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'employee_id' => null,
        'is_system' => false,
        'locale' => null,
        'desk_layout' => null,
        'page_panels' => null,
        'desk_quick_access' => null,
        'desk_panel_flags' => null,
        'nav_pins' => null,
        'nav_recent' => null,
        'notification_popups' => false,
        'tour_seen_at' => null,
        'email_two_factor_enabled' => false,
    ];

    /**
     * Remove the inbox with the user: notifications are polymorphic, so no
     * foreign key can cascade them.
     */
    protected static function booted(): void
    {
        static::deleted(function (User $user): void {
            UserNotification::query()->forUser($user)->delete();
        });
    }

    /**
     * This user's inbox, newest first, as UserNotification rows.
     *
     * @return MorphMany<UserNotification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(UserNotification::class, 'notifiable')->latest();
    }

    /**
     * The language notifications are written in for this user.
     */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * The browsers that skip this user's second sign-in step.
     *
     * @return HasMany<TrustedDevice, $this>
     */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * The lines on this user's private To-do desk panel.
     *
     * @return HasMany<UserTodo, $this>
     */
    public function todos(): HasMany
    {
        return $this->hasMany(UserTodo::class);
    }

    /**
     * This user's delivery settings; no row means the defaults
     * (NotificationPreference::for()).
     *
     * @return HasOne<NotificationPreference, $this>
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * The browsers and installed apps that receive this user's push.
     *
     * @return HasMany<PushSubscription, $this>
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * The files this user uploaded.
     *
     * `media.uploaded_by` restricts on delete, so a user who has uploaded
     * anything cannot be removed.
     *
     * @return HasMany<Media, $this>
     */
    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(Media::class, 'uploaded_by');
    }

    /**
     * Delete the model inside a transaction.
     *
     * HasRoles and HasPermissions detach the user's roles and direct
     * permissions while deleting. When a restricting foreign key (reports,
     * uploads, created tasks) then refuses the delete, the detach must be rolled
     * back with it, or a user who is kept silently loses their access.
     */
    public function delete(): bool
    {
        return (bool) DB::transaction(fn () => parent::delete());
    }

    /**
     * Give this account access with a starting role (also made its active
     * role). Accounts themselves come from HR (hub:import-hr) or user:create.
     */
    public function grantAccess(string $roleName): void
    {
        $role = Role::findOrCreate($roleName);
        $this->assignRole($role);
        $this->setActiveRole($role->id);
        $this->recordRoleChange([], [$role->name]);
    }

    /**
     * The role shown as this user's primary role.
     *
     * A user can hold several roles at once (see HasRoles); this is display
     * only — every assigned role still contributes its permissions (Spatie's
     * normal additive behavior). It does not narrow what the user can do.
     *
     * @return BelongsTo<Role, $this>
     */
    public function activeRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'active_role_id');
    }

    /**
     * Mark one of this user's already-assigned roles as active.
     *
     * Silently no-ops for a role the user does not hold, so a stale request
     * can never point active_role_id at a role that was just unassigned.
     */
    public function setActiveRole(?int $roleId): void
    {
        if ($roleId !== null && ! $this->roles()->whereKey($roleId)->exists()) {
            return;
        }

        $this->forceFill(['active_role_id' => $roleId])->save();
    }

    /**
     * Whether each password sign-in must be followed by an emailed code: the
     * user turned it on, or their role requires two-factor authentication.
     */
    public function usesEmailTwoFactor(): bool
    {
        return ($this->email_two_factor_enabled && $this->email !== '')
            || EnsureTwoFactorIsEnabled::emailCodeRequiredFor($this);
    }

    /**
     * Whether this account may no longer sign in. A system account never
     * loses access; any other account needs an active linked employee, so one
     * with no employee, or with an inactive or missing one, is refused.
     */
    public function hasLostAccess(): bool
    {
        if ($this->getAttribute('is_system')) {
            return false;
        }

        $employeeId = $this->getAttribute('employee_id');

        if ($employeeId === null) {
            return true;
        }

        return ! $this->employee()->where('is_active', true)->exists();
    }

    /**
     * Every account's display name and email by id (remembered): the
     * employee's English name, or the email without an employee.
     *
     * @return array<int, array{name: string, email: string}>
     */
    public static function directory(): array
    {
        return static::rememberLookup('directory', fn (): array => static::query()
            ->toBase()
            ->leftJoin('employees', 'employees.id', '=', 'users.employee_id')
            ->get(['users.id', 'users.email', 'employees.first_name_en', 'employees.last_name_en'])
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => [
                'name' => trim($row->first_name_en.' '.$row->last_name_en) ?: (string) $row->email,
                'email' => (string) $row->email,
            ]])
            ->all());
    }

    /**
     * The user's name: their employee's English name, or the email.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->directoryEntry()['name'] ?? (string) ($this->attributes['email'] ?? ''));
    }

    /**
     * The login email: the column when it was selected, else the remembered
     * directory (a relation loaded as `user:id` still has it).
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::get(fn (mixed $value): string => array_key_exists('email', $this->attributes)
            ? (string) $value
            : ($this->directoryEntry()['email'] ?? ''));
    }

    /**
     * @return array{name: string, email: string}|null
     */
    private function directoryEntry(): ?array
    {
        $id = $this->getKey();

        if (! is_numeric($id)) {
            return null;
        }

        $directory = static::directory();

        if (! array_key_exists((int) $id, $directory)) {
            static::forgetLookups();
            $directory = static::directory();
        }

        return $directory[(int) $id] ?? null;
    }

    /**
     * Accounts that may still sign in: system accounts, and accounts with an
     * active employee (as hasLostAccess() decides).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withActiveAccount(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where($query->qualifyColumn('is_system'), true)
            ->orWhereHas('employee', fn (Builder $employee) => $employee->where('is_active', true)));
    }

    /**
     * Accounts that are not system accounts and have no active employee
     * (none, or an inactive or missing one), the complement of
     * withActiveAccount().
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withoutActiveAccount(Builder $query): void
    {
        $query->where($query->qualifyColumn('is_system'), false)
            ->whereDoesntHave('employee', fn (Builder $employee) => $employee->where('is_active', true));
    }

    /**
     * Users whose email or employee name (English or Arabic) contains the search.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function whereNameOrEmailLike(Builder $query, string $search): void
    {
        $query->where(Like::everyWord($search, fn (Builder|QueryBuilder $query, string $word) => $query
            ->where(Like::word('users.email', $word))
            ->orWhereIn('users.employee_id', app(EmployeeLookup::class)->idsNamedLike($word))));
    }

    /**
     * Order users by their display name (employee English name, else email), then id.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function orderByName(Builder $query): void
    {
        $query->orderByRaw("COALESCE((SELECT NULLIF(TRIM(CONCAT(employees.first_name_en, ' ', employees.last_name_en)), '') FROM employees WHERE employees.id = users.employee_id), users.email)")
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * The HR department of this user's current position, or null when they
     * have no linked employee or no active contract.
     */
    public function departmentId(): ?int
    {
        // An explicit load, not a lazy one: this runs for users read in bulk
        // too (notification recipients), where lazy loading is refused.
        return $this->loadMissing('employee')->employee?->currentDepartmentId();
    }

    /**
     * HR employee ids in this user's current department. Memoized per user
     * instance, since task visibility reads it on every task query.
     *
     * @return list<int>
     */
    public function departmentEmployeeIds(): array
    {
        return once(function (): array {
            $departmentId = $this->departmentId();

            return $departmentId === null
                ? []
                : app(EmployeeLookup::class)->idsInDepartment($departmentId);
        });
    }

    /**
     * Whether this user may read their whole HR department's tasks — the desk
     * Tasks panel's `department` scope and the department-tasks page (W-14).
     *
     * D-65 makes this an HR job level, not a role or a permission: only a
     * department manager qualifies, so a Managers or General Manager role
     * holder gets it only when their level matches. Administrator roles keep
     * it through the administrator-roles rule. Memoized per user instance,
     * since the panel and the policy both read it.
     */
    public function canSeeDepartmentTasks(): bool
    {
        return once(fn (): bool => $this->isAdministrator()
            || ($this->employee_id !== null && EmployeeProfile::isDepartmentManager($this->employee_id)));
    }

    /**
     * Tracking user ids of everyone in this user's current HR department.
     * Memoized per user instance, since task edit checks read it per task.
     *
     * @return list<int>
     */
    public function departmentUserIds(): array
    {
        return once(fn (): array => $this->departmentEmployeeIds() === []
            ? []
            : array_values(static::query()->whereIn('employee_id', $this->departmentEmployeeIds())->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()));
    }

    /**
     * Programs this user manages (programs.manager_id is their HR employee).
     * Memoized per user instance.
     *
     * @return list<int>
     */
    public function managedProgramIds(): array
    {
        return once(fn (): array => $this->employee_id === null
            ? []
            : array_values(Program::query()->where('manager_id', $this->employee_id)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()));
    }

    /**
     * Whether this user sees every department's tasks by default, with a
     * filter to narrow down, instead of being hard-scoped to their own.
     *
     * Drives whether the department filter dropdown is shown at all.
     */
    public function canViewAllTaskDepartments(): bool
    {
        return $this->hasAnyRole(config('access-control.task_department_scope_bypass_roles', []));
    }

    /**
     * Whether this user's default task visibility is hard-scoped to their
     * own HR department, with no filter able to widen it.
     *
     * Only the named team roles are scoped this way, and a bypass role always
     * wins: someone holding both (e.g. Managers and Team Member) sees every
     * department. An account holding neither sees every department too.
     */
    public function isTaskDepartmentScoped(): bool
    {
        return $this->hasAnyRole(config('access-control.task_department_scoped_roles', []))
            && ! $this->canViewAllTaskDepartments();
    }

    /**
     * The URL of this user's HR photo, or HR's default picture without one.
     */
    public function avatarUrl(): string
    {
        return app(EmployeeLookup::class)->avatarUrl($this->employee_id);
    }

    /**
     * The user's own HR photo, or null when HR holds none.
     */
    public function photoUrl(): ?string
    {
        return app(EmployeeLookup::class)->photoUrl($this->employee_id);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'email_two_factor_enabled' => 'boolean',
            'is_system' => 'boolean',
            'notification_popups' => 'boolean',
            'tour_seen_at' => 'datetime',
            'desk_layout' => 'array',
            'page_panels' => 'array',
            'desk_quick_access' => 'array',
            'desk_panel_flags' => 'array',
            'nav_pins' => 'array',
            'nav_recent' => 'array',
        ];
    }

    /**
     * Whether the user holds an administrator role (config access-control.administrator_roles),
     * who sees every page, panel and action of the Desk.
     */
    public function isAdministrator(): bool
    {
        return $this->hasRole(config('access-control.administrator_roles', []));
    }

    public function activityModule(): string
    {
        return 'access-management';
    }

    public function activityLabel(): string
    {
        return $this->name;
    }

    public function activityProgramId(): ?int
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function activityEvents(): array
    {
        return [];
    }

    /**
     * Account housekeeping (last seen, preferences, 2FA) is not news.
     */
    protected function activityRecordsUpdates(): bool
    {
        return false;
    }

    /**
     * Record a change of roles (role pivots fire no model events).
     *
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    public function recordRoleChange(array $before, array $after): void
    {
        sort($before);
        sort($after);

        if ($before === $after) {
            return;
        }

        $this->recordActivity('user.role_changed', ['roles' => [$before, $after]], ['role' => $after === [] ? '—' : implode(', ', $after)]);
    }

    /**
     * Record a change of direct permissions (permission pivots fire no model
     * events either).
     *
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    public function recordPermissionChange(array $before, array $after): void
    {
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if ($added === [] && $removed === []) {
            return;
        }

        sort($added);
        sort($removed);

        $this->recordActivity('user.permissions_changed', properties: [
            'added' => $added === [] ? '—' : implode(', ', $added),
            'removed' => $removed === [] ? '—' : implode(', ', $removed),
            'added_permissions' => $added,
            'removed_permissions' => $removed,
        ]);
    }

    /**
     * Record that this user was refused an action (a 403). Logged for the
     * audit trail only: it notifies nobody.
     *
     * @param  array{action: string, ip: string|null}  $attempt
     */
    public function recordAccessDenied(array $attempt): void
    {
        app(ActivityRecorder::class)->record($this, 'user.access_denied', properties: $attempt, notify: false);
    }
}
