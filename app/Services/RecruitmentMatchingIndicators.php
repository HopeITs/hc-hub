<?php

namespace App\Services;

use App\Enums\RecruitmentCvAvailability;
use App\Enums\RecruitmentIndicatorOutcome as Outcome;
use App\Models\City;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentApplicationIndicatorSnapshot;
use App\Models\RecruitmentIndicatorCriteriaVersion;
use App\Models\RecruitmentRequestPositionDetail;
use App\Models\User;
use App\Support\Recruitment\MatchingIndicator;
use App\Support\Recruitment\MatchingIndicatorSet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Explainable matching indicators: an aid for the reviewer, not a decision.
 *
 * Every indicator is a plain rule from config('recruitment.indicators') —
 * compare this answer with that requirement — and returns what it compared
 * and why it concluded what it did. There is no weighting and no model: the
 * only summary is "n of m assessable criteria met", and each of the n and m
 * is a row the reviewer can read.
 *
 * Missing data is never a miss. A question an older form version did not
 * ask, an answer left blank, a vacancy with no requirement recorded — each
 * is Unknown or NotApplicable and drops out of the count.
 *
 * This class reads applications and never writes one. The only thing it
 * saves is a snapshot row of its own, always with its criteria version.
 */
class RecruitmentMatchingIndicators
{
    /** Bump when the rules below change shape; config changes re-hash on their own. */
    public const SCHEMA = 1;

    public const KEYS = [
        'city_match',
        'experience',
        'qualification',
        'industry_experience',
        'previous_applicant',
        'cv_availability',
    ];

    public const EAGER = [
        'applicant',
        'answers',
        'formVersion',
        'cvFiles',
        'campaign.vacancy.createdFromRequest.latestVersion.positionDetail',
    ];

    /** @var array<int, City>|null */
    private ?array $cities = null;

    public function __construct(private readonly RecruitmentApplicationFilters $filters) {}

    // --------------------------------------------
    // Criteria
    // --------------------------------------------

    /** @return array<string, mixed> */
    public function criteriaDefinition(): array
    {
        return [
            'schema' => self::SCHEMA,
            'indicators' => self::KEYS,
            'rules' => config('recruitment.indicators', []),
        ];
    }

    public function criteriaVersion(): string
    {
        return 'v'.self::SCHEMA.'-'.substr(hash('sha256', json_encode($this->criteriaDefinition())), 0, 12);
    }

    // --------------------------------------------
    // Evaluation
    // --------------------------------------------

    public function evaluate(RecruitmentApplication $application): MatchingIndicatorSet
    {
        return $this->evaluateMany(new Collection([$application]))[$application->getKey()];
    }

    /**
     * One pass for a page of applications: relations are loaded once and the
     * previous-applicant flag is one query for all of them.
     *
     * @param  Collection<int, RecruitmentApplication>  $applications
     * @return array<int, MatchingIndicatorSet> keyed by application id
     */
    public function evaluateMany(Collection $applications): array
    {
        if ($applications->isEmpty()) {
            return [];
        }

        $applications->loadMissing(self::EAGER);
        $previous = array_flip($this->previousApplicationIds($applications->modelKeys()));
        $version = $this->criteriaVersion();

        return $applications->mapWithKeys(fn (RecruitmentApplication $application): array => [
            $application->getKey() => new MatchingIndicatorSet($version, [
                $this->cityMatch($application),
                $this->experience($application),
                $this->qualification($application),
                $this->industryExperience($application),
                $this->previousApplicant(isset($previous[$application->getKey()])),
                $this->cvAvailability($application),
            ]),
        ])->all();
    }

    // --------------------------------------------
    // Snapshots
    // --------------------------------------------

    public function snapshot(RecruitmentApplication $application, ?User $actor = null): RecruitmentApplicationIndicatorSnapshot
    {
        return $this->snapshotMany(new Collection([$application]), $actor)[0];
    }

    /**
     * Saves the counts together with the criteria version they were computed
     * under, and that version's definition, in one transaction — a figure is
     * never stored without what it means.
     *
     * @param  Collection<int, RecruitmentApplication>  $applications
     * @return array<int, RecruitmentApplicationIndicatorSnapshot>
     */
    public function snapshotMany(Collection $applications, ?User $actor = null): array
    {
        $sets = $this->evaluateMany($applications);

        return DB::transaction(function () use ($sets, $actor): array {
            RecruitmentIndicatorCriteriaVersion::query()->insertOrIgnore([
                'version' => $this->criteriaVersion(),
                'definition_json' => json_encode($this->criteriaDefinition()),
                'created_at' => now(),
            ]);

            $snapshots = [];
            foreach ($sets as $applicationId => $set) {
                $snapshots[] = RecruitmentApplicationIndicatorSnapshot::query()->updateOrCreate(
                    ['application_id' => $applicationId],
                    [
                        'criteria_version' => $set->criteriaVersion,
                        'met_count' => $set->metCount(),
                        'assessed_count' => $set->assessedCount(),
                        'indicators_json' => $set->toArray(),
                        'computed_at' => now(),
                        'computed_by_user_id' => $actor?->getKey(),
                    ],
                );
            }

            return $snapshots;
        });
    }

    // --------------------------------------------
    // Indicators
    // --------------------------------------------

    /** The vacancy's governorate against the city the applicant gave. */
    private function cityMatch(RecruitmentApplication $application): MatchingIndicator
    {
        $key = 'city_match';
        $vacancyCity = $this->city($application->campaign?->vacancy?->createdFromRequest?->scope_city_id);

        if ($vacancyCity === null) {
            return new MatchingIndicator($key, Outcome::NotApplicable, 'city_no_vacancy_city');
        }

        if ($vacancyCity->slug === City::NATIONAL_SLUG) {
            return new MatchingIndicator($key, Outcome::NotApplicable, 'city_national');
        }

        [$applicantCity, $source, $raw] = $this->applicantCity($application);

        if ($applicantCity === null) {
            return $raw === null
                ? new MatchingIndicator($key, Outcome::Unknown, 'city_not_stated', evidence: ['vacancy' => $vacancyCity->name])
                : new MatchingIndicator($key, Outcome::Unknown, 'city_unrecognised', ['value' => $raw], ['applicant' => $raw, 'vacancy' => $vacancyCity->name]);
        }

        $evidence = ['applicant' => $applicantCity->name, 'vacancy' => $vacancyCity->name, 'source' => $source];
        $params = ['applicant' => $applicantCity->name, 'vacancy' => $vacancyCity->name, 'source' => __('recruitment-applicant.indicators.sources.'.$source)];

        return (int) $applicantCity->getKey() === (int) $vacancyCity->getKey()
            ? new MatchingIndicator($key, Outcome::Met, 'city_same', $params, $evidence)
            : new MatchingIndicator($key, Outcome::NotMet, 'city_different', $params, $evidence);
    }

    private function experience(RecruitmentApplication $application): MatchingIndicator
    {
        $key = 'experience';
        $required = $this->detail($application)?->required_experience_years;

        if ($required === null) {
            return new MatchingIndicator($key, Outcome::NotApplicable, 'experience_no_requirement');
        }

        $required = (int) $required;
        if ($required <= 0) {
            return new MatchingIndicator($key, Outcome::Met, 'experience_none_required', evidence: ['required' => 0]);
        }

        [$state, $value] = $this->answer($application, 'experience_years');
        if ($state !== 'answered') {
            return new MatchingIndicator($key, Outcome::Unknown, 'experience_'.$state, ['required' => $required], ['required' => $required]);
        }

        $range = config('recruitment.indicators.experience_ranges.'.$value);
        $label = $this->optionLabel($application, 'experience_years', $value);
        if (! is_array($range)) {
            return new MatchingIndicator($key, Outcome::Unknown, 'experience_unrecognised', ['value' => $label], ['applicant' => $label, 'required' => $required]);
        }

        [$min, $max] = $range;
        $params = ['answer' => $label, 'required' => $required];
        $evidence = ['applicant' => $label, 'required' => $required];

        return match (true) {
            $min >= $required => new MatchingIndicator($key, Outcome::Met, 'experience_met', $params, $evidence),
            $max !== null && $max < $required => new MatchingIndicator($key, Outcome::NotMet, 'experience_below', $params, $evidence),
            default => new MatchingIndicator($key, Outcome::Partial, 'experience_overlaps', $params, $evidence),
        };
    }

    /**
     * required_qualifications is free text, so the level is read from words
     * it contains — and when none is found the indicator says so and leaves
     * the comparison to the reviewer rather than guessing.
     */
    private function qualification(RecruitmentApplication $application): MatchingIndicator
    {
        $key = 'qualification';
        $text = trim((string) $this->detail($application)?->required_qualifications);

        if ($text === '') {
            return new MatchingIndicator($key, Outcome::NotApplicable, 'qualification_no_requirement');
        }

        $excerpt = Str::limit($text, 160);
        [$requiredLevel, $keyword] = $this->requiredLevel($text);

        if ($requiredLevel === null) {
            return new MatchingIndicator($key, Outcome::Unknown, 'qualification_unrecognised', ['requirement' => $excerpt], ['requirement' => $excerpt]);
        }

        $ranks = config('recruitment.indicators.education_ranks', []);
        $requiredLabel = __('recruitment-applicant.indicators.levels.'.$requiredLevel);

        [$state, $value] = $this->answer($application, 'education_level');
        if ($state !== 'answered') {
            return new MatchingIndicator($key, Outcome::Unknown, 'qualification_'.$state, ['required' => $requiredLabel], ['requirement' => $excerpt]);
        }

        $label = $this->optionLabel($application, 'education_level', $value);
        if (! isset($ranks[$value])) {
            return new MatchingIndicator($key, Outcome::Unknown, 'qualification_answer_unrecognised', ['value' => $label], ['applicant' => $label, 'requirement' => $excerpt]);
        }

        $params = ['answer' => $label, 'required' => $requiredLabel, 'keyword' => $keyword];
        $evidence = ['applicant' => $label, 'required' => $requiredLabel, 'requirement' => $excerpt];

        return $ranks[$value] >= $ranks[$requiredLevel]
            ? new MatchingIndicator($key, Outcome::Met, 'qualification_met', $params, $evidence)
            : new MatchingIndicator($key, Outcome::NotMet, 'qualification_below', $params, $evidence);
    }

    /** Self-declared, and labelled as such: nothing here checks the CV. */
    private function industryExperience(RecruitmentApplication $application): MatchingIndicator
    {
        $key = 'industry_experience';
        [$fieldState, $field] = $this->answer($application, 'experience_field');

        if ($fieldState === 'answered') {
            $label = $this->optionLabel($application, 'experience_field', $field);

            return in_array($field, config('recruitment.indicators.no_experience_answers', []), true)
                ? new MatchingIndicator($key, Outcome::NotMet, 'industry_none_declared', ['field' => $label], ['applicant' => $label])
                : new MatchingIndicator($key, Outcome::Met, 'industry_declared', ['field' => $label], ['applicant' => $label]);
        }

        [$textState] = $this->answer($application, 'experience_text');
        if ($textState === 'answered') {
            return new MatchingIndicator($key, Outcome::Partial, 'industry_described_only');
        }

        return new MatchingIndicator(
            $key,
            Outcome::Unknown,
            $fieldState === 'not_asked' && $textState === 'not_asked' ? 'industry_not_asked' : 'industry_not_answered',
        );
    }

    /** A flag for context. Never scored: nobody gains or loses for having applied before. */
    private function previousApplicant(bool $isPrevious): MatchingIndicator
    {
        return new MatchingIndicator(
            'previous_applicant',
            Outcome::Info,
            $isPrevious ? 'previous_yes' : 'previous_no',
            evidence: ['previous' => $isPrevious],
            scored: false,
        );
    }

    private function cvAvailability(RecruitmentApplication $application): MatchingIndicator
    {
        $key = 'cv_availability';
        $availability = RecruitmentCvAvailability::of($application->cvFiles);

        return match ($availability) {
            RecruitmentCvAvailability::Available => new MatchingIndicator($key, Outcome::Met, 'cv_available'),
            RecruitmentCvAvailability::Pending => new MatchingIndicator($key, Outcome::Unknown, 'cv_pending'),
            RecruitmentCvAvailability::Archived => new MatchingIndicator($key, Outcome::NotMet, 'cv_archived'),
            RecruitmentCvAvailability::Blocked => new MatchingIndicator($key, Outcome::NotMet, 'cv_blocked'),
            default => $this->formField($application, 'cv') === null && $application->formVersion !== null
                ? new MatchingIndicator($key, Outcome::NotApplicable, 'cv_not_requested')
                : new MatchingIndicator($key, Outcome::NotMet, 'cv_missing'),
        };
    }

    // --------------------------------------------
    // Reading the application
    // --------------------------------------------

    /**
     * @return array{0: 'answered'|'not_answered'|'not_asked', 1: string|null}
     */
    private function answer(RecruitmentApplication $application, string $role): array
    {
        $fieldKey = (string) config('recruitment.indicators.fields.'.$role, $role);
        $answer = $application->answers->firstWhere('field_key', $fieldKey);
        $value = $answer?->answer_json['value'] ?? null;

        if (is_string($value) || is_int($value) || is_float($value)) {
            $value = trim((string) $value);
            if ($value !== '') {
                return ['answered', $value];
            }
        }

        // An answer row with nothing usable in it is the same as no answer;
        // a field the form version never had is a question never asked.
        return [$answer === null && $this->formField($application, $fieldKey) === null ? 'not_asked' : 'not_answered', null];
    }

    /** @return array<string, mixed>|null */
    private function formField(RecruitmentApplication $application, string $fieldKey): ?array
    {
        foreach ((array) ($application->formVersion?->fields_json ?? []) as $field) {
            if (is_array($field) && ($field['key'] ?? null) === $fieldKey) {
                return $field;
            }
        }

        return null;
    }

    /** The option's label as the form version showed it, or the raw value. */
    private function optionLabel(RecruitmentApplication $application, string $role, string $value): string
    {
        $field = $this->formField($application, (string) config('recruitment.indicators.fields.'.$role, $role));

        foreach ((array) ($field['options'] ?? []) as $option) {
            if (is_array($option) && (string) ($option['value'] ?? '') === $value) {
                $labels = (array) ($option['label'] ?? []);
                $label = $labels[app()->getLocale()] ?? $labels['en'] ?? null;

                if (is_string($label) && $label !== '') {
                    return $label;
                }
            }
        }

        return $value;
    }

    /**
     * Preferred vacancy city first, then the applicant's recorded city, then
     * a residence answer that names a governorate exactly.
     *
     * @return array{0: City|null, 1: string|null, 2: string|null} city, source, raw unrecognised value
     */
    private function applicantCity(RecruitmentApplication $application): array
    {
        [$state, $preferred] = $this->answer($application, 'preferred_city');
        if ($state === 'answered') {
            $slug = config('recruitment.indicators.city_answers.'.$preferred);
            $city = is_string($slug) ? $this->cityBySlug($slug) : null;

            if ($city !== null) {
                return [$city, 'preferred', null];
            }
        }

        if (($city = $this->city($application->applicant?->city_id)) !== null) {
            return [$city, 'profile', null];
        }

        [$residenceState, $residence] = $this->answer($application, 'residence');
        if ($residenceState === 'answered') {
            $needle = mb_strtolower($residence);
            foreach ($this->cities() as $candidate) {
                if (in_array($needle, [mb_strtolower((string) $candidate->name_en), mb_strtolower((string) $candidate->name_ar)], true)) {
                    return [$candidate, 'residence', null];
                }
            }
        }

        return [null, null, $state === 'answered' ? $preferred : ($residenceState === 'answered' ? $residence : null)];
    }

    /** @return array{0: string|null, 1: string|null} lowest level named, and the word that named it */
    private function requiredLevel(string $text): array
    {
        $haystack = mb_strtolower($text);
        $ranks = config('recruitment.indicators.education_ranks', []);
        $found = null;
        $foundKeyword = null;

        foreach (config('recruitment.indicators.qualification_keywords', []) as $level => $keywords) {
            foreach ((array) $keywords as $keyword) {
                if (! isset($ranks[$level]) || ! str_contains($haystack, mb_strtolower((string) $keyword))) {
                    continue;
                }

                if ($found === null || $ranks[$level] < $ranks[$found]) {
                    $found = $level;
                    $foundKeyword = (string) $keyword;
                }
            }
        }

        return [$found, $foundKeyword];
    }

    private function detail(RecruitmentApplication $application): ?RecruitmentRequestPositionDetail
    {
        return $application->campaign?->vacancy?->createdFromRequest?->latestVersion?->positionDetail;
    }

    /**
     * @param  array<int, int>  $applicationIds
     * @return array<int, int>
     */
    private function previousApplicationIds(array $applicationIds): array
    {
        $query = RecruitmentApplication::query();

        return $query
            ->whereKey($applicationIds)
            ->whereExists($this->filters->earlierApplication($query))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function city(mixed $id): ?City
    {
        return $id === null ? null : ($this->cities()[(int) $id] ?? null);
    }

    private function cityBySlug(string $slug): ?City
    {
        foreach ($this->cities() as $city) {
            if (strtoupper((string) $city->slug) === strtoupper($slug)) {
                return $city;
            }
        }

        return null;
    }

    /** Governorates are a handful of rows; read them once per instance. */
    private function cities(): array
    {
        return $this->cities ??= City::query()->get()->keyBy('id')->all();
    }
}
