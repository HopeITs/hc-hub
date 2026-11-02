<?php

namespace Modules\Media\Support;

use App\Models\User;

/**
 * The media pages a user may open, in menu order: what media-request had in
 * its top bar. The module's own page links (x-media.shell) and the hub
 * sidebar (App\Support\Navigation) both read this list, so the two cannot
 * drift apart. A requester only gets the home screen; their requests open
 * inside it.
 */
final class MediaNavigation
{
    public function __construct(private readonly MediaTeam $team) {}

    /**
     * @return list<array{key: string, route: string, icon: string, active: list<string>}>
     */
    public function pagesFor(User $user): array
    {
        $viewsOwn = $user->hasActivePermission('media.requests.view-own');

        if ($this->team->isRequesterOnly($user)) {
            return $viewsOwn ? [self::page('home', 'media-requests.home', 'home', ['media-requests.home'])] : [];
        }

        $employee = MediaTeam::employeeOf($user);
        $isMediaTeam = $this->team->isMember($employee) || $this->team->canActAsMediaManager($user);
        $managesPrograms = $this->team->managedProgramIds($employee) !== [] || $user->myRole() === 'super-admin';

        return array_values(array_filter([
            $viewsOwn ? self::page('home', 'media-requests.home', 'home', ['media-requests.home']) : null,
            $viewsOwn ? self::page('my', 'media-requests.index', 'inbox', ['media-requests.index', 'media-requests.show']) : null,
            $managesPrograms && $user->hasActivePermission('media.requests.approve') ? self::page('approvals', 'media-requests.approvals', 'shield', ['media-requests.approvals']) : null,
            $isMediaTeam && $user->hasActivePermission('media.requests.work') ? self::page('board', 'media-requests.board', 'board', ['media-requests.board']) : null,
            $viewsOwn ? self::page('drive', 'media-requests.drive', 'layers', ['media-requests.drive']) : null,
            $isMediaTeam && $user->hasActivePermission('media.requests.work') ? self::page('stats', 'media-requests.stats', 'chart', ['media-requests.stats']) : null,
            $user->hasActivePermission('media.requests.create') ? self::page('pack', 'media-requests.pack', 'box', ['media-requests.pack']) : null,
            $isMediaTeam && $user->hasActivePermission('media.requests.view-log') ? self::page('log', 'media-requests.log', 'list', ['media-requests.log']) : null,
            $isMediaTeam && $user->hasActivePermission('media.requests.view-log') ? self::page('assistant', 'media-requests.assistant', 'spark', ['media-requests.assistant']) : null,
        ]));
    }

    /**
     * @param  list<string>  $active
     * @return array{key: string, route: string, icon: string, active: list<string>}
     */
    private static function page(string $key, string $route, string $icon, array $active): array
    {
        return compact('key', 'route', 'icon', 'active');
    }
}
