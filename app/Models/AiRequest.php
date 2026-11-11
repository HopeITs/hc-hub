<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiRequestStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AiRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One logged call to the AI provider. The usage ledger behind cost reports,
 * speed checks and the trial's proof of value; never shown to normal users.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $feature
 * @property string $provider
 * @property string $model
 * @property AiRequestStatus $status
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $duration_ms
 * @property string|null $error
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'feature', 'provider', 'model', 'status', 'input_tokens', 'output_tokens', 'duration_ms', 'error'])]
class AiRequest extends Model
{
    /** @use HasFactory<AiRequestFactory> */
    use HasFactory;

    /**
     * Mirrors the column defaults.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'input_tokens' => 0,
        'output_tokens' => 0,
        'duration_ms' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AiRequestStatus::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * The person whose action triggered the call, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
