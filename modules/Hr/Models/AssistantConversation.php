<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One chat with the AI assistant (App\Livewire\Assistant\Chat). The messages
 * are encrypted: an answer may quote the employee's own record or pay.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property array<mixed>|null $messages
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'title', 'messages'])]
class AssistantConversation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'messages' => 'encrypted:array',
        ];
    }

    /**
     * Chats kept per user; the oldest beyond this are deleted.
     */
    public const KEEP = 50;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOwnedBy(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }
}
