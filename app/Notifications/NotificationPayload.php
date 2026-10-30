<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;

/**
 * Everything one notification needs, independent of who receives it.
 *
 * Either `key` + `params` (tracking style: rendered in the reader's language
 * at display time by NotificationPresenter) or `content`, the data a
 * notification class built itself (HR's `title` / `body` per language, `url`
 * and `subject`), which is stored as it is.
 */
final readonly class NotificationPayload
{
    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>|null  $content  stored as-is instead of key/params
     * @param  array{route?: string, parameters?: array<string, mixed>}  $link
     * @param  list<array<string, mixed>>  $actions  quick actions
     * @param  bool  $direct  addressed to the reader personally, so a module mute does not silence it
     */
    public function __construct(
        public NotificationKind $kind,
        public string $module,
        public NotificationPriority $priority,
        public string $key = '',
        public array $params = [],
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        public ?int $actorId = null,
        public ?int $activityId = null,
        public ?string $groupKey = null,
        public array $link = [],
        public array $actions = [],
        public ?array $content = null,
        public bool $direct = true,
    ) {}
}
