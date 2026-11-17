<?php

declare(strict_types=1);

namespace Modules\Tracking\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProjectMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Enums\ProjectMemberSource;

/**
 * One person's part on a project team, with dates (roadmap 2.2, D4). The
 * member is an employee; added_by and ended_by are the logins that acted.
 * Ending a membership fills ends_on and the row stays, so the team's history
 * is kept. A null starts_on means the start is not known. Membership grants
 * no permission. Writers: Modules\Tracking\Actions\ProjectMembers\*.
 *
 * @property int $id
 * @property int $project_id
 * @property int $employee_id
 * @property ProjectMemberRole $role
 * @property CarbonImmutable|null $starts_on
 * @property CarbonImmutable|null $ends_on
 * @property int|null $added_by
 * @property int|null $ended_by
 * @property ProjectMemberSource $source
 * @property int|null $is_open
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * The `employee` relation is registered by TrackingServiceProvider, so this
 * model does not import HR.
 */
#[Fillable(['project_id', 'employee_id', 'role', 'starts_on', 'ends_on', 'added_by', 'ended_by', 'source'])]
class ProjectMember extends Model
{
    /** @use HasFactory<ProjectMemberFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function isCurrent(): bool
    {
        return $this->ends_on === null;
    }

    /**
     * Memberships still running.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->whereNull('project_members.ends_on');
    }

    /**
     * Memberships that have ended.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ended(Builder $query): void
    {
        $query->whereNotNull('project_members.ends_on');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'employee_id' => 'integer',
            'role' => ProjectMemberRole::class,
            'source' => ProjectMemberSource::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'added_by' => 'integer',
            'ended_by' => 'integer',
            'is_open' => 'integer',
        ];
    }
}
