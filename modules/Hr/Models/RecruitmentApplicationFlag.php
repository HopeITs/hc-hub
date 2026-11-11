<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * @property int $id
 * @property int $application_id
 * @property string $type
 * @property string $category
 * @property string $reason
 * @property string|null $note
 * @property int $created_by_user_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentApplicationFlag extends Model
{
    public const TYPE_GREEN = 'green';

    public const TYPE_RED = 'red';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [self::TYPE_GREEN, self::TYPE_RED];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function reasons(): array
    {
        return [
            'technical' => ['strong' => 'strong', 'below_requirement' => 'below_requirement'],
            'ethics' => ['integrity' => 'integrity', 'concern' => 'concern'],
            'communication' => ['strong' => 'strong', 'weak' => 'weak'],
            'reliability' => ['dependable' => 'dependable', 'concern' => 'concern'],
            'experience' => ['relevant' => 'relevant', 'insufficient' => 'insufficient'],
            'eligibility' => ['verified' => 'verified', 'issue' => 'issue'],
        ];
    }

    public function typeLabel(): string
    {
        return __('recruitment-applicant.flags.types.'.$this->type);
    }

    public function categoryLabel(): string
    {
        return __('recruitment-applicant.flags.categories.'.$this->category);
    }

    public function reasonLabel(): string
    {
        return __('recruitment-applicant.flags.reasons.'.$this->reason);
    }
}
