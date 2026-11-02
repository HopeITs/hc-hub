<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One person's home page layout and private notes. */
class MediaDashboard extends Model
{
    protected $fillable = ['user_id', 'layout', 'notes'];

    protected $casts = ['layout' => 'array'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
