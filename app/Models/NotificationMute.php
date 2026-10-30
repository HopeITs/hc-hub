<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An inbox module one user has muted. Direct notifications ignore it.
 *
 * @property int $id
 * @property int $user_id
 * @property string $module
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class NotificationMute extends Model
{
    protected $fillable = ['user_id', 'module'];

    /**
     * The user who muted the module.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
