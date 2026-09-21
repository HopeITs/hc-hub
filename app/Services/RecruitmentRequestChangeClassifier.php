<?php

namespace App\Services;

use App\Enums\WorkflowStepKey;
use App\Models\RecruitmentRequestVersion;
use DateTimeInterface;

/**
 * How severely an edit invalidates the approvals already given (RV-1).
 *
 * Comparing version N+1 to version N field by field answers one question: how
 * far back does the workflow have to go. Moving the position to another
 * programme is not the same as rewording the duties, and asking three people
 * to approve the second again wastes their time as surely as skipping them on
 * the first would waste the approval.
 *
 * Every editable field is classified. RV-1 is explicit that an unclassified
 * field means a change that quietly invalidates nothing when it should have
 * invalidated something, so a field added to the request details has to be
 * listed here before it ships.
 */
class RecruitmentRequestChangeClassifier
{
    /** Nothing changed. */
    public const NOTHING = 0;

    /** Wording. Nothing is invalidated; the workflow resumes where it stopped. */
    public const DESCRIPTIVE = 1;

    /** Money. Financial review and everything after it are invalidated. */
    public const FINANCIAL = 2;

    /** Structure. Every approval is invalidated; the workflow starts over. */
    public const SUBSTANTIVE = 3;

    /**
     * Columns on recruitment_request_position_details, by category.
     *
     * direct_supervisor_position_id is treated as substantive although RV-1's
     * table does not name it: it decides where the position sits in the org
     * chart, exactly as programme, centre and department do, and leaving it out
     * is the unclassified-field problem RV-1 warns about. Worth confirming.
     *
     * @var array<int, array<int, string>>
     */
    private const DETAIL_FIELDS = [
        self::SUBSTANTIVE => [
            'position_id', 'program_id', 'center_id', 'department_id', 'job_level_id',
            'current_headcount', 'requested_headcount', 'direct_supervisor_position_id',
        ],
        self::FINANCIAL => [
            'salary_amount', 'currency', 'salary_per', 'contract_type_id',
        ],
        self::DESCRIPTIVE => [
            'recruitment_reason', 'other_reason', 'main_duties', 'required_qualifications',
            'required_experience_years', 'required_skills', 'detailed_explanation',
            'expected_start_date', 'volunteer_period_start_date', 'volunteer_period_end_date',
            'weekly_hours', 'priority', 'volunteer_type', 'role_title_en', 'role_title_ar',
            'reason', 'role_description_ready', 'finance_consulted', 'org_chart_updated',
        ],
    ];

    /**
     * Fields RV-1 lists as substantive that live on the request, not on the
     * details row. They are read from the version snapshot instead.
     *
     * @var array<int, string>
     */
    private const SNAPSHOT_FIELDS = ['request_type', 'city_id', 'vacancy_id'];

    /**
     * The severest category of anything that changed.
     *
     * Severest, because a single edit can touch more than one category and the
     * workflow has to go back as far as the worst of them requires.
     */
    public function classify(?RecruitmentRequestVersion $previous, RecruitmentRequestVersion $current): int
    {
        // The first version invalidates nothing; there is nothing before it.
        if ($previous === null) {
            return self::NOTHING;
        }

        $severest = self::NOTHING;

        foreach ($this->changes($previous, $current) as $change) {
            $severest = max($severest, $change['category']);
        }

        return $severest;
    }

    /**
     * Every field that changed, with its old and new value and its category.
     *
     * This is what RV-1b needs: a notification saying "the request was edited"
     * without saying which fields and to what is explicitly not acceptable.
     *
     * @return array<string, array{category: int, from: mixed, to: mixed}>
     */
    public function changes(?RecruitmentRequestVersion $previous, RecruitmentRequestVersion $current): array
    {
        if ($previous === null) {
            return [];
        }

        $before = $previous->positionDetail;
        $after = $current->positionDetail;
        $changes = [];

        foreach (self::DETAIL_FIELDS as $category => $fields) {
            foreach ($fields as $field) {
                $from = $before?->{$field};
                $to = $after?->{$field};

                if (! $this->same($from, $to)) {
                    $changes[$field] = ['category' => $category, 'from' => $from, 'to' => $to];
                }
            }
        }

        $beforeSnapshot = (array) ($previous->snapshot_json ?? []);
        $afterSnapshot = (array) ($current->snapshot_json ?? []);

        foreach (self::SNAPSHOT_FIELDS as $field) {
            $from = $beforeSnapshot[$field] ?? null;
            $to = $afterSnapshot[$field] ?? null;

            if (! $this->same($from, $to)) {
                $changes[$field] = ['category' => self::SUBSTANTIVE, 'from' => $from, 'to' => $to];
            }
        }

        return $changes;
    }

    /**
     * The step a restarted workflow picks up from.
     *
     * Returns null for a descriptive change, meaning "carry on where you were"
     * — there is no step to go back to.
     */
    public function restartsFrom(int $category): ?int
    {
        return match ($category) {
            self::SUBSTANTIVE => WorkflowStepKey::ManagerApproval,
            self::FINANCIAL => WorkflowStepKey::FinanceApproval,
            default => null,
        };
    }

    /**
     * Loose enough that 5 and '5' are the same answer, strict enough that null
     * and 0 are not: a cleared field and a zeroed one mean different things.
     */
    private function same(mixed $from, mixed $to): bool
    {
        if ($from === null || $to === null) {
            return $from === $to;
        }

        if ($from instanceof DateTimeInterface || $to instanceof DateTimeInterface) {
            return $this->asDate($from) === $this->asDate($to);
        }

        if (is_bool($from) || is_bool($to)) {
            return (bool) $from === (bool) $to;
        }

        return (string) $from === (string) $to;
    }

    private function asDate(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
