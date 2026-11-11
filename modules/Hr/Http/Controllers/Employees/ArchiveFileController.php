<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Folder;
use Modules\Hr\Support\Employees\HrMedia;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Preview or download a file kept in an archive folder. The file follows its
 * folder: FolderPolicy::view decides.
 */
class ArchiveFileController extends Controller
{
    /** What an archive folder may hold. */
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'xlsx', 'pptx'];

    private const PREVIEWABLE = ['jpg', 'jpeg', 'png', 'pdf'];

    public function preview(int $media): Response
    {
        $file = $this->authorizedFile($media);

        abort_unless(in_array(HrMedia::extension($file), self::PREVIEWABLE, true), 404);

        return HrMedia::response($file, inline: true);
    }

    public function download(int $media): Response
    {
        $file = $this->authorizedFile($media);

        abort_unless(in_array(HrMedia::extension($file), self::ALLOWED_EXTENSIONS, true), 404);

        return HrMedia::response($file);
    }

    private function authorizedFile(int $media): stdClass
    {
        $file = HrMedia::find($media);

        abort_if($file === null || $file->model_type !== HrMedia::FOLDER, 404);

        Gate::authorize('view', Folder::query()->findOrFail((int) $file->model_id));

        return $file;
    }
}
