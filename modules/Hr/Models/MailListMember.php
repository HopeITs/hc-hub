<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MailListMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One address on a mailing list. 'active': already in the group at the mail provider's control
 * panel; 'pending': added here, waiting for the person who holds that panel to add and confirm it
 * (mail-lists.confirm).
 *
 * @property int $id
 * @property int $mail_list_id
 * @property string $email
 * @property string $status
 * @property int|null $added_by_user_id
 * @property int|null $confirmed_by_user_id
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['mail_list_id', 'email', 'status', 'added_by_user_id', 'confirmed_by_user_id', 'confirmed_at'])]
class MailListMember extends Model
{
    /** @use HasFactory<MailListMemberFactory> */
    use HasFactory;

    public const ACTIVE = 'active';

    public const PENDING = 'pending';

    /** @var array<string, mixed> */
    protected $attributes = ['status' => self::ACTIVE];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<MailList, $this>
     */
    public function mailList(): BelongsTo
    {
        return $this->belongsTo(MailList::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', self::PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
