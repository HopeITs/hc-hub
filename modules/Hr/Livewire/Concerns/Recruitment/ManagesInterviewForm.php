<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentInterviewSchedulingService;
use Modules\Hr\Enums\RecruitmentApplicationStatus;
use Modules\Hr\Enums\RecruitmentInterviewLocationType;
use Modules\Hr\Enums\RecruitmentInterviewType;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentInterview;
use Modules\Hr\Models\RecruitmentInterviewParticipant;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;

/**
 * Form state of the interview form: schedule one for an application, or
 * edit one. On edit a change of time or place is a reschedule and needs a
 * reason; the service decides that from what changed. The status and start
 * time the form opened with are locked and handed to the service, which
 * refuses if someone else changed the interview in between.
 */
trait ManagesInterviewForm
{
    #[Locked]
    public ?int $applicationId = null;

    #[Locked]
    public ?int $interviewId = null;

    #[Locked]
    public ?string $expectedStatus = null;

    #[Locked]
    public ?string $expectedStartsAt = null;

    public string $type = RecruitmentInterviewType::Hr;

    /** Local wall-clock time, Y-m-d\TH:i, in the application timezone. */
    public string $startsAt = '';

    public int|string $duration = 60;

    public string $locationType = RecruitmentInterviewLocationType::InPerson;

    public string $locationValue = '';

    public string $meetingUrl = '';

    /** @var array<int|string, string> [user_id => role] */
    public array $participants = [];

    /** @var array<string, int|string> [required seat => attendee user id] */
    public array $requiredParticipants = [];

    /** @var array<string, int> [required seat => role owner user id] */
    #[Locked]
    public array $requiredParticipantOwners = [];

    public string $newParticipantId = '';

    public string $newParticipantRole = RecruitmentInterviewParticipant::ROLE_INTERVIEWER;

    /** Edit only: why the time or place is changing. Internal, never emailed. */
    public string $reason = '';

    public bool $notifyCandidate = true;

    protected function fillForEdit(RecruitmentInterview $interview, RecruitmentInterviewSchedulingService $scheduling): void
    {
        $this->interviewId = $interview->getKey();
        $this->applicationId = $interview->application_id;
        $this->expectedStatus = $interview->status;
        $this->expectedStartsAt = $interview->scheduled_at->toIso8601String();
        $this->type = $interview->interview_type;
        $this->startsAt = $interview->scheduled_at->copy()->setTimezone($scheduling->timezone())->format('Y-m-d\TH:i');
        $this->duration = $interview->ends_at ? (int) $interview->scheduled_at->diffInMinutes($interview->ends_at) : 60;
        $this->locationType = $interview->location_type;
        $this->locationValue = (string) $interview->location_value;
        // The link is loaded only for a viewer allowed to see it; the form is HR-only anyway.
        $this->meetingUrl = Gate::allows('viewMeetingLink', $interview) ? (string) $interview->meeting_url : '';
        $this->fillRequiredSeats($scheduling->requiredParticipantAssignments($this->viewer(), $interview->application, $interview));
        $this->participants = $interview->currentParticipants()
            ->whereNotIn('user_id', array_values($this->requiredParticipants))
            ->pluck('role', 'user_id')->all();
    }

    protected function fillForCreate(RecruitmentApplication $application, RecruitmentInterviewSchedulingService $scheduling): void
    {
        $this->applicationId = $application->getKey();
        $this->duration = (int) RecruitmentConfig::get('recruitment.interviews.default_duration_minutes', 60);
        $this->fillRequiredSeats($scheduling->requiredParticipantAssignments($this->viewer(), $application));

        $date = request()->query('date');
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && $date >= now()->toDateString()) {
            $this->startsAt = $date.'T09:00';
        }

        if ($application->status === RecruitmentApplicationStatus::Finalist) {
            $this->type = RecruitmentInterviewType::Final;
        }
    }

    /** Form state only; the list is validated again, user by user, on save. */
    public function addParticipant(): void
    {
        $userId = filter_var($this->newParticipantId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($userId === false || ! in_array($this->newParticipantRole, RecruitmentInterviewParticipant::roles(), true)) {
            return;
        }

        if (in_array($userId, array_map('intval', array_values($this->requiredParticipants)), true)) {
            return;
        }

        $this->participants[$userId] = $this->newParticipantRole;
        $this->newParticipantId = '';
        $this->resetErrorBag('participants');
    }

    public function removeParticipant(int $userId): void
    {
        unset($this->participants[$userId]);
    }

    /** @return array<string, mixed> what the service validates */
    protected function interviewInput(): array
    {
        return [
            'type' => $this->type,
            'startsAt' => $this->startsAt,
            'duration' => $this->duration,
            'locationType' => $this->locationType,
            'locationValue' => $this->locationValue,
            'meetingUrl' => $this->meetingUrl,
            'participants' => $this->participants,
            'requiredParticipants' => array_map('intval', $this->requiredParticipants),
        ];
    }

    /**
     * Active staff the panel can be picked from, by name: only active
     * employees can be added.
     *
     * @return array<int, string> user id => "name — email"
     */
    protected function staffOptions(): array
    {
        $directory = User::directory();

        return User::query()
            ->whereHas('employee', fn (Builder $employee): Builder => $employee->where('is_active', true))
            ->pluck('id')
            ->mapWithKeys(fn ($id): array => [(int) $id => ($directory[(int) $id]['name'] ?? '#'.$id).' — '.($directory[(int) $id]['email'] ?? '')])
            ->sort()
            ->all();
    }

    /** @param  array<string, array{owner_id: int, attendee_id: int}>  $assignments */
    private function fillRequiredSeats(array $assignments): void
    {
        $this->requiredParticipantOwners = collect($assignments)->mapWithKeys(fn (array $assignment, string $seat): array => [$seat => (int) $assignment['owner_id']])->all();
        $this->requiredParticipants = collect($assignments)->mapWithKeys(fn (array $assignment, string $seat): array => [$seat => (int) $assignment['attendee_id']])->all();
    }
}
