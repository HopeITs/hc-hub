<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Education;
use App\Models\Employee;
use App\Models\Experience;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeDocumentController extends Controller
{
    public function download(Media $media): BinaryFileResponse
    {
        $employee = $this->employeeFor($media);
        Gate::authorize('viewPrivateDocuments', $employee);

        abort_unless(is_file($media->getPath()), Response::HTTP_NOT_FOUND);

        $response = response()->download(
            $media->getPath(),
            $media->file_name,
            [
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    private function employeeFor(Media $media): Employee
    {
        $model = $media->model;

        if ($model instanceof Employee && $media->collection_name === 'attachments') {
            return $model;
        }

        if (($model instanceof Education || $model instanceof Course || $model instanceof Experience)
            && $media->collection_name === 'file') {
            return $model->employee()->firstOrFail();
        }

        abort(Response::HTTP_NOT_FOUND);
    }
}
