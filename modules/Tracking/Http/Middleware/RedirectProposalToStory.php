<?php

declare(strict_types=1);

namespace Modules\Tracking\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Support\ProposalStoryLink;
use Symfony\Component\HttpFoundation\Response;

/**
 * The proposal page lives inside the project Story (Tracking Desk stage 4,
 * user decision 2026-10-20 "remove the proposal page, redirect all"): every
 * GET of a proposal lands on its project's Story with that proposal shown in
 * the Proposal block, on the tab asked for (?tab= becomes ?ptab=), and the
 * record-agreement deep link carried over so its dialog opens there. A link
 * that names no tab, opened by a reviewer whose review still waits on them,
 * lands on their own review (ProposalStoryLink::review).
 *
 * A proposal on no project the viewer may open keeps the standalone page.
 */
class RedirectProposalToStory
{
    public function handle(Request $request, Closure $next): Response
    {
        $proposal = $request->route('proposal');
        $viewer = $request->user();

        if (! $request->isMethod('GET') || ! $proposal instanceof Proposal || $viewer?->can('projects.view') !== true) {
            return $next($request);
        }

        $tab = $request->query('ptab', $request->query('tab'));
        $tab = is_string($tab) && $tab !== '' ? $tab : null;
        $toReview = $tab === null && ProposalStoryLink::awaitsReviewBy($viewer, $proposal);

        $story = ProposalStoryLink::story(
            $viewer,
            $proposal,
            $toReview ? 'status' : $tab,
            $request->boolean('record_agreement'),
            $toReview ? ProposalStoryLink::MyReview : 'proposal',
        );

        return $story === null ? $next($request) : redirect()->to($story);
    }
}
