<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\ChatConversation;

/*
| The chat's realtime channels, registered by HrServiceProvider next to the
| platform's own routes/channels.php (which is Core's and may not name HR).
*/

// The chat's per-person channel (ChatConversationUpdated): ids only, and only
// the person themself may listen.
Broadcast::channel('chat.user.{id}', fn (User $user, int|string $id): bool => $user->id === (int) $id);

// One conversation's members, for "typing…" (sent browser to browser, never
// stored). Membership only, through the conversation policy: no role exception.
Broadcast::channel('chat.conversation.{conversationId}', function (User $user, int|string $conversationId): bool {
    $conversation = ChatConversation::query()->find((int) $conversationId);

    return $conversation !== null && Gate::forUser($user)->allows('view', $conversation);
});

// Who is online now: every signed-in employee, while the chat is on. Only
// the user id is shared; names come from the site.
Broadcast::channel('chat.online', function (User $user): array|false {
    return config('chat.enabled') && $user->employee?->is_active ? ['id' => $user->id] : false;
});
