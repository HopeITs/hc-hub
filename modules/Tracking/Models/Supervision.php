<?php

declare(strict_types=1);

namespace Modules\Tracking\Models;

use App\Concerns\RecordsActivity;
use App\Notifications\EmployeeRecipients;
use App\Notifications\TracksActivity;
use Carbon\CarbonImmutable;
use Database\Factories\SupervisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A supervision exception or addition on top of the HR direct manager
 * (contracts §10, D-13). Routing reads it through Modules\Tracking\Support\SupervisionMap;
 * the only writers are Modules\Tracking\Actions\Supervisions\{AddSupervision,RemoveSupervision}.
 * Supervision grants no permission and no visibility. A removed supervision
 * is ended (ends_on, ended_by), never deleted (roadmap 2.5); a null starts_on
 * means the start is not known.
 *
 * @property int $id
 * @property int $supervisor_employee_id
 * @property int $supervisee_employee_id
 * @property CarbonImmutable|null $starts_on
 * @property CarbonImmutable|null $ends_on
 * @property int|null $created_by
 * @property int|null $ended_by
 * @property int|null $is_open
 * @property int $pair_low
 * @property int $pair_high
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['supervisor_employee_id', 'supervisee_employee_id', 'starts_on', 'created_by'])]
class Supervision extends Model implements TracksActivity
{
    /** @use HasFactory<SupervisionFactory> */
    use HasFactory, RecordsActivity;

    /**
     * The tracking user who added the row.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'supervisor_employee_id' => 'integer',
            'supervisee_employee_id' => 'integer',
            'created_by' => 'integer',
            'ended_by' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_open' => 'integer',
            'pair_low' => 'integer',
            'pair_high' => 'integer',
        ];
    }

    public function activityModule(): string
    {
        return 'access-management';
    }

    public function activityProgramId(): ?int
    {
        return null;
    }

    /**
     * «Supervisor → Supervisee», in English like every stored label.
     */
    public function activityLabel(): string
    {
        $names = $this->activityNames();

        return ($names['supervisor']['en'] ?? '').' → '.($names['supervisee']['en'] ?? '');
    }

    /**
     * @return list<string>
     */
    public function activityEmployeeFields(): array
    {
        return ['supervisor_employee_id', 'supervisee_employee_id'];
    }

    /**
     * Rows are added and ended (removed), never otherwise edited.
     *
     * @return array<string, string>
     */
    public function activityEvents(): array
    {
        return ['ends_on' => 'removed'];
    }

    /**
     * Only filling ends_on is a removal.
     */
    protected function activityEventForChange(string $field, mixed $old, mixed $new): ?string
    {
        return $field === 'ends_on' && $old === null && $new !== null ? 'removed' : null;
    }

    public function isCurrent(): bool
    {
        return $this->ends_on === null;
    }

    /**
     * Supervisions still running.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function current(Builder $query): void
    {
        $query->whereNull('supervisions.ends_on');
    }

    protected function activityCreatedEvent(): string
    {
        return $this->getMorphClass().'.added';
    }

    protected function activityDeletedEvent(): string
    {
        return $this->getMorphClass().'.removed';
    }

    /**
     * Both people's ids and both-language names, so either sentence can be rendered.
     *
     * @return array<string, mixed>
     */
    protected function activityProperties(string $event): array
    {
        return [
            'supervisor_employee_id' => (int) $this->activityAttribute('supervisor_employee_id'),
            'supervisee_employee_id' => (int) $this->activityAttribute('supervisee_employee_id'),
            ...$this->activityNames(),
        ];
    }

    /**
     * @return array{supervisor: array{en: string, ar: string}|null, supervisee: array{en: string, ar: string}|null}
     */
    private function activityNames(): array
    {
        $supervisorId = (int) $this->activityAttribute('supervisor_employee_id');
        $superviseeId = (int) $this->activityAttribute('supervisee_employee_id');
        $names = app(EmployeeRecipients::class)->names([$supervisorId, $superviseeId]);

        return ['supervisor' => $names[$supervisorId] ?? null, 'supervisee' => $names[$superviseeId] ?? null];
    }
}
