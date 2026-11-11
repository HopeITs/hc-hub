<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Modules\Hr\Models\Employee;
use Symfony\Component\HttpFoundation\Response;

class EmployeeAvatarController extends Controller
{
    /**
     * Stream an employee's photo from the HR application's storage.
     *
     * HR treats staff photos as protection-sensitive and never puts them at a
     * guessable public URL, so tracking reads the file itself and hands it only
     * to signed-in users. An employee with no photo, or whose file is missing,
     * gets HR's default picture.
     */
    public function __invoke(int $employee): Response
    {
        $avatar = Employee::avatarOf($employee);
        $disk = Storage::disk('hr');

        if ($avatar === null || ! $disk->exists($avatar['path'])) {
            return response()
                ->file(public_path(Employee::DefaultAvatar), ['Cache-Control' => 'private, max-age=300']);
        }

        return $disk->response($avatar['path'], null, [
            'Content-Type' => $avatar['mime'],
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
