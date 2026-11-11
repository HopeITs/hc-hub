<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\DigitalId;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public digital ID card (/ID/{card}), in English, for whoever scans a
 * printed card. Every malformed, old or disabled link gets the same "not
 * valid" page with a 404, so links cannot be probed.
 */
class EmployeeCardController extends Controller
{
    public function show(string $card): View|Response
    {
        App::setLocale('en');

        $employee = DigitalId::resolve($card);

        if ($employee === null) {
            return response()->view('hr::pages.employees._card-invalid', [], 404);
        }

        $position = DigitalId::cardContract($employee)?->position;
        $city = $employee->city_id === null ? null : City::query()->select(['id', 'name_en', 'hr_hotline'])->find($employee->city_id);
        $emergency = trim((string) $city?->hr_hotline) !== '' ? $city->hr_hotline : $this->hrPhone($employee->city_id);

        return view('hr::pages.employees._card-public', [
            'name' => trim($employee->first_name_en.' '.$employee->last_name_en),
            'positionTitle' => $position?->title_en,
            'centerName' => $position?->center_id ? Center::query()->whereKey($position->center_id)->value('name_en') : null,
            'cityName' => $city?->name_en,
            'photoUrl' => URL::temporarySignedRoute('hr.employees.card.photo', now()->addMinutes(10), ['employee' => $employee->id]),
            'emergency' => $emergency,
            'emergencyHref' => $this->phoneHref($emergency),
            'phone' => $employee->work_phone,
            'phoneHref' => $this->phoneHref($employee->work_phone),
            'telegramHref' => $this->linkHref($employee->telegram_work, 'https://t.me/', '@'),
            'linkedinHref' => $this->linkHref($employee->linkedin, 'https://www.linkedin.com/in/', '/'),
        ]);
    }

    /**
     * The card's photo: a short-lived signed link minted by show(), revoked
     * as soon as the employee is deactivated or the card disabled.
     */
    public function photo(Request $request, int $employee): Response
    {
        abort_unless($request->hasValidSignature(), Response::HTTP_FORBIDDEN);
        abort_unless(Employee::query()->whereKey($employee)->where('is_active', true)->where('digital_id_enabled', true)->exists(), Response::HTTP_NOT_FOUND);

        $avatar = Employee::avatarOf($employee);
        $disk = Storage::disk('hr');

        if ($avatar === null || ! $disk->exists($avatar['path'])) {
            return response()->file(public_path(Employee::DefaultAvatar), ['Cache-Control' => 'private, max-age=300']);
        }

        return $disk->response($avatar['path'], null, [
            'Content-Type' => $avatar['mime'],
            'Cache-Control' => 'private, max-age=300, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    /**
     * The first active HR-department employee's work phone in the city.
     */
    private function hrPhone(?int $cityId): ?string
    {
        if ($cityId === null) {
            return null;
        }

        return Employee::query()
            ->where('is_active', true)
            ->where('city_id', $cityId)
            ->whereNotNull('work_phone')
            ->where('work_phone', '<>', '')
            ->whereHas('contracts.position.department', fn ($department) => $department->where('code', 'HR'))
            ->orderBy('id')
            ->value('work_phone');
    }

    private function phoneHref(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        return $phone === '' ? null : 'tel:'.preg_replace('/[^\d+]/', '', $phone);
    }

    private function linkHref(?string $value, string $base, string $trim): ?string
    {
        $value = trim((string) $value);

        return match (true) {
            $value === '' => null,
            str_starts_with($value, 'https://'), str_starts_with($value, 'http://') => $value,
            default => $base.trim($value, $trim),
        };
    }
}
