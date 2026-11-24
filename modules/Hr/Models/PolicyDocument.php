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
 * @property bool $required_for_onboarding
 * @property string $onboarding_kind
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['source', 'external_id', 'title', 'mime_type', 'source_url', 'content', 'source_modified_at', 'synced_at', 'required_for_onboarding', 'onboarding_kind'])]
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
            'required_for_onboarding' => 'boolean',
        ];
    }

    public const SOURCE_DRIVE = 'drive';

    public const SOURCE_PANEL = 'panel';

    /** Read and agreed. */
    public const KIND_POLICY = 'policy';

    /** Made by another team (the Media guideline) and only reviewed. */
    public const KIND_REVIEW = 'review';

    /** @var list<string> */
    public const KINDS = [self::KIND_POLICY, self::KIND_REVIEW];

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
