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
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'address', 'position'])]
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

    /** @var array<string, mixed> */
    protected $attributes = ['position' => 0];

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
