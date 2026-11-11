<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $source
 * @property string|null $external_id
 * @property string $title
 * @property string|null $mime_type
 * @property string|null $source_url
 * @property string|null $content
 * @property mixed|null $source_modified_at
 * @property mixed|null $synced_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['source', 'external_id', 'title', 'mime_type', 'source_url', 'content', 'source_modified_at', 'synced_at'])]
class PolicyDocument extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_modified_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public const SOURCE_DRIVE = 'drive';

    public const SOURCE_PANEL = 'panel';

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
