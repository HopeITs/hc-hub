<?php

declare(strict_types=1);

namespace Modules\Hr\Models\Concerns;

use App\Enums\Locale;
use App\Support\EmployeeProfile;
use App\Support\Like;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Center;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;

/**
 * How every module shows an employee: names in both languages, the HR photo,
 * the picker list and the «position · level · department · center» summary.
 *
 * Photos are HR's Spatie media rows, imported as `hr_media`; the files stay
 * on the `hr` disk (HR's storage folder) until the media move.
 *
 * @phpstan-require-extends Employee
 */
trait PresentsEmployee
{
    /**
     * The columns needed to show an employee by name, for eager loads and pickers.
     *
     * @var list<string>
     */
    public const NameColumns = ['id', 'first_name_en', 'last_name_en', 'first_name_ar', 'last_name_ar'];

    /**
     * HR's own picture for an employee with no photo, copied into public/images.
     */
    public const DefaultAvatar = 'images/default-avatar.png';

    private const PickerCacheKeyPrefix = 'hub.employees.picker.';

    private const PickerCacheSeconds = 300;

    private const AvatarCacheKey = 'hub.employees.avatars';

    /**
     * Where each Spatie disk of the HR app keeps its files, inside HR's storage/app.
     *
     * @var array<string, string>
     */
    private const AvatarDiskFolders = ['public' => 'public', 'local' => 'private'];

    /**
     * The ids of employees whose English or Arabic name matches the search:
     * every word must appear in one of the name columns. Prefer a whereHas()
     * on the employee relation in queries; this is for in-memory filters.
     *
     * @return list<int>
     */
    public static function idsMatchingName(string $search, bool $acrossLayouts = true): array
    {
        if (trim(Like::term($search)) === '') {
            return [];
        }

        return array_values(static::query()
            ->whereNameLike($search, $acrossLayouts)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * Where the employee's photo sits on the `hr` disk, or null without one.
     *
     * Every photo row is read in one query and cached for five minutes. Only
     * image files with a plain file name count.
     *
     * @return array{path: string, mime: string, version: int}|null
     */
    public static function avatarOf(int $employeeId): ?array
    {
        /** @var array<int, array{path: string, mime: string, version: int}> $avatars */
        $avatars = Cache::memo()->remember(self::AvatarCacheKey, self::PickerCacheSeconds, function (): array {
            $avatars = [];

            $rows = DB::table('hr_media')
                ->select(['id', 'model_id', 'file_name', 'mime_type', 'disk', 'updated_at'])
                ->where('model_type', 'App\Models\Employee')
                ->where('collection_name', 'avatar')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                $folder = self::AvatarDiskFolders[$row->disk] ?? null;
                $fileName = (string) $row->file_name;

                if ($folder === null || $fileName === '' || basename($fileName) !== $fileName || ! str_starts_with((string) $row->mime_type, 'image/')) {
                    continue;
                }

                $avatars[(int) $row->model_id] = [
                    'path' => $folder.'/'.$row->id.'/'.$fileName,
                    'mime' => (string) $row->mime_type,
                    'version' => $row->updated_at === null ? (int) $row->id : (int) strtotime((string) $row->updated_at),
                ];
            }

            return $avatars;
        });

        return $avatars[$employeeId] ?? null;
    }

    /**
     * The URL of the employee's photo, or the default picture without one.
     */
    public static function avatarUrl(?int $employeeId): string
    {
        return self::photoUrl($employeeId) ?? asset(self::DefaultAvatar);
    }

    /**
     * The URL of the employee's own photo, or null without one. It goes
     * through EmployeeAvatarController (signed-in users only); `v` changes
     * when the photo is replaced.
     */
    public static function photoUrl(?int $employeeId): ?string
    {
        $avatar = $employeeId === null ? null : self::avatarOf($employeeId);

        if ($avatar === null) {
            return null;
        }

        return route('employees.avatar', ['employee' => $employeeId, 'v' => $avatar['version']]);
    }

    /**
     * Every employee, active or not, ordered for a picker, name columns only.
     * Cached for five minutes per sort language and memoised per request.
     *
     * @return Collection<int, self>
     */
    public static function pickerOptions(): Collection
    {
        $suffix = Locale::current() === Locale::Arabic ? 'ar' : 'en';

        $rows = Cache::memo()->remember(
            self::PickerCacheKeyPrefix.$suffix,
            self::PickerCacheSeconds,
            fn (): array => static::query()
                ->select(self::NameColumns)
                ->orderBy('first_name_'.$suffix)
                ->orderBy('last_name_'.$suffix)
                ->orderBy('id')
                ->toBase()
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all(),
        );

        return static::hydrate($rows)->values();
    }

    /**
     * The picker list as plain `{value, label, detail, photo, inactive}` rows:
     * label is the main and secondary name, detail the current position,
     * photo the picture; someone without an active contract (or inactive) is
     * flagged inactive.
     *
     * @return SupportCollection<int, array{value: string, label: string, detail: string, photo: string, inactive: bool}>
     */
    public static function pickerLabels(): SupportCollection
    {
        $suffix = Locale::current() === Locale::Arabic ? 'ar' : 'en';

        return collect(Cache::memo()->remember(
            self::PickerCacheKeyPrefix.'labels-with-photo.'.$suffix,
            self::PickerCacheSeconds,
            fn (): array => static::pickerOptions()->map(fn (Employee $employee): array => [
                'value' => (string) $employee->id,
                'label' => $employee->name.' · '.$employee->secondary_name,
                'detail' => static::positionSummary($employee->id) ?? (string) __('common.no_active_position'),
                'photo' => static::avatarUrl($employee->id),
                'inactive' => ! (EmployeeProfile::for($employee->id)['is_active'] ?? false),
            ])->all(),
        ));
    }

    /**
     * Where an employee sits now: «position · job level · department ·
     * center» in the interface language, null without an active contract.
     * The job level is left out when it only repeats the position title.
     */
    public static function positionSummary(int $employeeId): ?string
    {
        $profile = EmployeeProfile::for($employeeId);

        if ($profile === null) {
            return null;
        }

        $title = Locale::current() === Locale::Arabic && trim((string) $profile['position_title_ar']) !== ''
            ? $profile['position_title_ar']
            : $profile['position_title_en'];

        $jobLevel = $profile['job_level'];

        if (mb_strtolower(trim((string) $jobLevel)) === mb_strtolower(trim((string) $title))) {
            $jobLevel = null;
        }

        return collect([
            $title,
            $jobLevel,
            $profile['department_id'] === null ? null : Department::nameOf($profile['department_id']),
            Center::nameOf($profile['center_id']),
        ])->map(fn (?string $part): string => trim((string) $part))->filter()->implode(' · ');
    }

    /**
     * Drop the cached picker lists so the next request reloads them.
     */
    public static function forgetPickerOptions(): void
    {
        foreach (['en', 'ar'] as $suffix) {
            Cache::memo()->forget(self::PickerCacheKeyPrefix.$suffix);
            Cache::memo()->forget(self::PickerCacheKeyPrefix.'labels-with-photo.'.$suffix);
        }
    }

    /**
     * Drop the cached photo list after a photo is uploaded or removed.
     */
    public static function forgetAvatars(): void
    {
        Cache::memo()->forget(self::AvatarCacheKey);
        static::forgetPickerOptions();
    }

    /**
     * The employee's name in the other language, shown beside the main name.
     *
     * @return Attribute<string, never>
     */
    protected function secondaryName(): Attribute
    {
        return Attribute::get(fn (): string => Locale::current() === Locale::Arabic ? $this->name_en : $this->name_ar);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function nameEn(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name_en.' '.$this->last_name_en));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function nameAr(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name_ar.' '.$this->last_name_ar));
    }

    /**
     * The department of this employee's current position (active contract),
     * read through EmployeeProfile.
     */
    public function currentDepartmentId(): ?int
    {
        return EmployeeProfile::for($this->id)['department_id'] ?? null;
    }
}
