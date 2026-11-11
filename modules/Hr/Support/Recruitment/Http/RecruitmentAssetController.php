<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment\Http;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The fixed Hope Center artwork and fonts the job-post graphics are drawn
 * with (the old app kept them under public/). Only the files named here are
 * served, so the route cannot be used to read anything else.
 */
final class RecruitmentAssetController extends Controller
{
    private const FILES = [
        'arrow.svg' => 'image/svg+xml',
        'arrow2.svg' => 'image/svg+xml',
        'hiring.svg' => 'image/svg+xml',
        'line.svg' => 'image/svg+xml',
        'loc.svg' => 'image/svg+xml',
        'logo.svg' => 'image/svg+xml',
        'hc-logo.svg' => 'image/svg+xml',
        'somar-regular.ttf' => 'font/ttf',
        'somar-bold.ttf' => 'font/ttf',
        'somar-black.ttf' => 'font/ttf',
    ];

    public function __invoke(string $file): BinaryFileResponse
    {
        abort_unless(isset(self::FILES[$file]), 404);

        return response()->file(dirname(__DIR__).'/assets/'.$file, [
            'Content-Type' => self::FILES[$file],
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
