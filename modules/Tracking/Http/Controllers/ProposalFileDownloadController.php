<?php

declare(strict_types=1);

namespace Modules\Tracking\Http\Controllers;

use App\Exceptions\UnreadableStoredFile;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Support\StoredFiles;
use App\Support\UploadName;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Tracking\Models\Proposal;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProposalFileDownloadController extends Controller
{
    /**
     * Stream a proposal file to the browser.
     *
     * The media row must belong to the proposal in the URL — the same IDOR
     * guard ProjectFileDownloadController carries.
     */
    public function __invoke(Proposal $proposal, Media $media): StreamedResponse
    {
        Gate::authorize('view', $proposal);

        abort_unless(
            ($media->mediable_type === 'proposal' && $media->mediable_id === $proposal->id)
            || ($media->mediable_type === 'proposal_status_note'
                && $proposal->statusNotes()->whereKey($media->mediable_id)->exists()),
            404
        );

        abort_if(
            $media->collection->isFundingPaper() && ! auth()->user()->can('funding-agreements.view'),
            403
        );

        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        try {
            return StoredFiles::stream($media->disk, $media->path, $media->encryption_key_id, UploadName::clean($media->original_name), $media->type, [
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (UnreadableStoredFile $exception) {
            throw $exception->forRow('media', $media->id);
        }
    }
}
