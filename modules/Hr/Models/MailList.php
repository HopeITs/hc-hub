<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MailListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;

/**
 * A mailing list of the organisation (managers@, hope.aleppo@ ...): its own address and the work
 * emails of its members. The app only reads it to email a list or share a folder with its
 * members; the real distribution is the mail provider's.
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property int $position
 * @property string|null $rule_type
 * @property int|null $rule_city_id
 * @property int|null $rule_program_id
 * @property int|null $rule_department_id
 * @property bool $rule_managers_only
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'address', 'position', 'rule_type', 'rule_city_id', 'rule_program_id', 'rule_department_id', 'rule_managers_only'])]
class MailList extends Model
{
    /** @use HasFactory<MailListFactory> */
    use HasFactory;

    /** The list every member of staff belongs to. */
    public const ALL_STAFF_ADDRESS = 'team@hcsyria.org';

    /** The domain of the organisation's own mailboxes. */
    public const DOMAIN = 'hcsyria.org';

    /** The groups the page offers, in order; 'other' catches anything new. */
    public const CATEGORIES = ['organisation', 'functions', 'cities', 'projects'];

    /** What a list is for: see MailListSync. */
    public const RULE_ALL_STAFF = 'all_staff';

    public const RULE_BOARD = 'board';

    public const RULE_CUSTOM = 'custom';

    public const RULES = [self::RULE_ALL_STAFF, self::RULE_BOARD, self::RULE_CUSTOM];

    /** @var array<string, mixed> */
    protected $attributes = ['position' => 0, 'rule_managers_only' => false];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['rule_managers_only' => 'boolean', 'rule_city_id' => 'integer', 'rule_program_id' => 'integer', 'rule_department_id' => 'integer'];
    }

    /**
     * The link that opens a new message in the viewer's mail program: To the list, Cc the
     * viewer's direct manager. The sender is the account the mail program is signed in with:
     * a mailto: link cannot choose it.
     */
    public function mailto(): string
    {
        $cc = self::viewerManagerEmail();
        $cc = filled($cc) && strcasecmp($cc, $this->address) !== 0 ? $cc : null;

        // '@' stays as it is: it is legal in a mailto: and some mail programs choke on %40.
        $plain = fn (string $address): string => str_replace('%40', '@', rawurlencode($address));

        return 'mailto:'.$plain($this->address).($cc !== null ? '?cc='.$plain($cc) : '');
    }

    /**
     * The viewer's direct manager's work email, looked up once per request for all the links of a page.
     */
    private static function viewerManagerEmail(): ?string
    {
        static $memo = [];

        $employeeId = auth()->user()?->employee_id;

        if ($employeeId === null) {
            return null;
        }

        $key = spl_object_id(app()).':'.$employeeId;

        return $memo[$key] ??= (Employee::directManagerEmails([(int) $employeeId])[(int) $employeeId] ?? '');
    }

    public function hasRule(): bool
    {
        return $this->rule_type !== null;
    }

    /**
     * The list that stands for a place or a project, to email it as a whole: the one whose rule is
     * exactly this city / project / department (and not "managers only"). Null when there is none.
     *
     * @param  Collection<int, self>|null  $lists  the lists to choose from (all with a custom rule when null)
     */
    public static function emailFor(?int $cityId, ?int $programId, ?int $departmentId, ?Collection $lists = null): ?self
    {
        if ($cityId === null && $programId === null && $departmentId === null) {
            return null;
        }

        $lists ??= self::customRuled();

        return $lists->first(fn (self $list): bool => ! $list->rule_managers_only
            && $list->rule_city_id === $cityId
            && $list->rule_program_id === $programId
            && $list->rule_department_id === $departmentId);
    }

    /**
     * The list for a project or department group inside a city: the one of that city, else the one
     * for the project or department everywhere.
     *
     * @param  Collection<int, self>|null  $lists
     */
    public static function emailForGroup(?int $cityId, ?int $programId, ?int $departmentId, ?Collection $lists = null): ?self
    {
        $lists ??= self::customRuled();

        return self::emailFor($cityId, $programId, $departmentId, $lists)
            ?? self::emailFor(null, $programId, $departmentId, $lists);
    }

    /**
     * Every list, grouped by category in the page's order, for a menu to choose from.
     *
     * @return Collection<string, Collection<int, self>>
     */
    public static function menuGroups(): Collection
    {
        $lists = self::query()->orderBy('position')->orderBy('name')->get()
            ->groupBy(fn (self $list): string => self::categoryOf($list->address));

        return collect([...self::CATEGORIES, 'other'])
            ->filter(fn (string $key): bool => $lists->has($key))
            ->mapWithKeys(fn (string $key): array => [$key => $lists->get($key)]);
    }

    /**
     * The lists worth putting first for a place, project or department: the one for exactly this
     * combination, then the one for each part on its own. Empty when nothing matches.
     *
     * @return Collection<int, self>
     */
    public static function suggestionsFor(?int $cityId, ?int $programId, ?int $departmentId): Collection
    {
        $lists = self::customRuled();

        return collect([
            self::emailFor($cityId, $programId, $departmentId, $lists),
            self::emailFor($cityId, null, null, $lists),
            self::emailFor(null, $programId, null, $lists),
            self::emailFor(null, null, $departmentId, $lists),
        ])->filter()->unique('id')->values();
    }

    /**
     * The lists with a place/project/department rule.
     *
     * @return Collection<int, self>
     */
    public static function customRuled(): Collection
    {
        return self::query()->where('rule_type', self::RULE_CUSTOM)->orderBy('position')->get();
    }

    /** Which group a list belongs to, from the name of its address. */
    public static function categoryOf(string $address): string
    {
        $name = strtolower(strstr($address, '@', true) ?: $address);

        return match (true) {
            in_array($name, ['team', 'managers', 'board', 'headoffice'], true) => 'organisation',
            in_array($name, ['pr', 'meal', 'financial', 'logistics', 'ct', 'design', 'it'], true) => 'functions',
            $name === 'homs', str_starts_with($name, 'hope.') => 'cities',
            str_starts_with($name, 'micro.'), str_starts_with($name, 'studyzone.'),
            in_array($name, ['disc.syria', 'relief.aleppo', 'leap.students'], true) => 'projects',
            default => 'other',
        };
    }

    /**
     * @return HasMany<MailListMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(MailListMember::class);
    }

    /**
     * The accounts of the members that have one, to share a folder with.
     *
     * @return Builder<User>
     */
    public function users(): Builder
    {
        return User::query()->whereIn('email', $this->members()->select('email'));
    }

    /**
     * A new account on the organisation's domain joins the all-staff list as pending: it is in the
     * group here at once and waits for the control panel's holder to add it there. Does nothing
     * when the list is not set up, the address is not the organisation's, or it is already on it.
     */
    public static function queueNewAccount(User $user): void
    {
        $email = strtolower((string) ($user->getAttributes()['email'] ?? ''));

        if (! str_ends_with($email, '@'.self::DOMAIN)) {
            return;
        }

        static::query()->where('address', self::ALL_STAFF_ADDRESS)->first()?->members()->firstOrCreate(
            ['email' => $email],
            ['status' => MailListMember::PENDING, 'added_by_user_id' => auth()->id()],
        );
    }
}
