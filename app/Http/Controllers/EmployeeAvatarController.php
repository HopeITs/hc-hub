<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\AvatarThumbnail;
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
     * to signed-in users, as a small cached thumbnail (AvatarThumbnail) when
     * the file is an image GD can read, else as the original. An employee with
     * no photo, or whose file is missing, gets HR's default picture.
     */
    public function __invoke(int $employee): Response
    {
        $avatar = Employee::avatarOf($employee);
        $disk = Storage::disk('hr');

        if ($avatar === null || ! $disk->exists($avatar['path'])) {
            return response()
                ->file(public_path(Employee::DefaultAvatar), ['Cache-Control' => 'private, max-age=300']);
        }

        $thumbnail = AvatarThumbnail::pathFor($avatar['path'], $avatar['version']);

        if ($thumbnail !== null) {
            return response()->file($thumbnail, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ])->setPrivate();
        }

        return $disk->response($avatar['path'], null, [
            'Content-Type' => $avatar['mime'],
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
