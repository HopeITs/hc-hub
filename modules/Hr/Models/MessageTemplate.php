<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A saved email or WhatsApp text with {placeholders}. Nothing here is sent
 * by the system: MessageTemplateRenderer fills it in for one person and the
 * result opens as a new message the user reviews first.
 *
 * @property int $id
 * @property string $name
 * @property string $channel
 * @property string $language
 * @property string|null $subject
 * @property string $body
 * @property bool $cc_manager
 * @property bool $is_active
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'channel', 'language', 'subject', 'body', 'cc_manager', 'is_active', 'created_by'])]
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory;

    public const EMAIL = 'email';

    public const WHATSAPP = 'whatsapp';

    /** @var array<string, mixed> */
    protected $attributes = [
        'language' => 'en',
        'cc_manager' => false,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cc_manager' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isWhatsapp(): bool
    {
        return $this->channel === self::WHATSAPP;
    }
}
