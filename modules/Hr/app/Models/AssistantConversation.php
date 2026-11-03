<?php

namespace Modules\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One chat with the AI assistant (Modules\Hr\Livewire\Assistant\Chat). The
 * messages are encrypted: an answer may quote the employee's own record.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property list<array<string, mixed>>|null $messages
 */
class AssistantConversation extends Model
{
    /** Chats kept per user; the oldest beyond this are deleted. */
    public const KEEP = 50;

    protected $fillable = ['user_id', 'title', 'messages'];

    protected $casts = [
        'messages' => 'encrypted:array',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeOwnedBy(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }
}
