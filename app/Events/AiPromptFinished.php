<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A queued AI prompt finished for one user. Broadcast on their private
 * channel as ".ai.finished", so the page that asked can show the draft
 * without waiting or reloading.
 *
 * Sent immediately: it is only raised from the already-queued RunAiPrompt job.
 */
final class AiPromptFinished implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $userId,
        public string $requestKey,
        public bool $succeeded,
        public ?string $text,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'ai.finished';
    }

    /**
     * @return array{key: string, succeeded: bool, text: string|null}
     */
    public function broadcastWith(): array
    {
        return [
            'key' => $this->requestKey,
            'succeeded' => $this->succeeded,
            'text' => $this->text,
        ];
    }
}
