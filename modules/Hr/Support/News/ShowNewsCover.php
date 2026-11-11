<?php

declare(strict_types=1);

namespace Modules\Hr\Support\News;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Hr\Models\News;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams a news item's cover from the HR storage to whoever may read the
 * item (or manages it). Covers are never at a guessable public URL.
 */
final class ShowNewsCover
{
    public function __invoke(News $news): Response
    {
        if (! Gate::any(['view', 'manage'], $news)) {
            abort(403);
        }

        $cover = NewsCover::of((int) $news->id);
        $disk = Storage::disk('hr');

        abort_if($cover === null || ! $disk->exists($cover['path']), 404);

        return $disk->response($cover['path'], null, [
            'Content-Type' => $cover['mime'],
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
