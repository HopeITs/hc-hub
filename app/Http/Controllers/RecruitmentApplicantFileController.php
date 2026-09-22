<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentApplicantFile;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecruitmentApplicantFileController extends Controller
{
    public function download(RecruitmentApplicantFile $file): BinaryFileResponse
    {
        Gate::authorize('download', $file);

        $media = $file->getFirstMedia('document');
        abort_unless(
            $media instanceof Media
                && $media->disk === config('recruitment-uploads.clean_disk')
                && is_file($media->getPath()),
            Response::HTTP_NOT_FOUND,
        );

        $extension = strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));
        $downloadName = $file->file_type.($extension === '' ? '' : ".{$extension}");
        $response = response()->download($media->getPath(), $downloadName, [
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }
}
