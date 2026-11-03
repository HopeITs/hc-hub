<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PolicyDocument extends Model
{
    public const SOURCE_DRIVE = 'drive';

    public const SOURCE_PANEL = 'panel';

    protected $fillable = [
        'source',
        'external_id',
        'title',
        'mime_type',
        'source_url',
        'content',
        'source_modified_at',
        'synced_at',
    ];

    protected $casts = [
        'source_modified_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * Documents with text the assistant can read.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeReadable(Builder $query): Builder
    {
        return $query->whereNotNull('content')->where('content', '!=', '');
    }
}
