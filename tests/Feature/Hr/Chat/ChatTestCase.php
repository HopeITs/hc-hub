<?php

namespace Tests\Feature\Hr\Chat;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\User;
use Modules\Hr\Models\ChatConversation;
use Modules\Hr\Support\Chat\ChatGroupService;
use Modules\Hr\Support\Chat\ChatService;
use Tests\TestCase;

abstract class ChatTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('chat-files');
    }

    protected function chat(): ChatService
    {
        return app(ChatService::class);
    }

    protected function groupService(): ChatGroupService
    {
        return app(ChatGroupService::class);
    }

    protected function person(): User
    {
        return User::factory()->create();
    }

    protected function deactivate(User $user): void
    {
        $user->employee->forceFill(['is_active' => false])->save();
        $user->refresh();
    }

    /** @return array{0: ChatConversation, 1: User, 2: User} */
    protected function privateChat(): array
    {
        $a = $this->person();
        $b = $this->person();

        return [$this->chat()->openPrivate($a, $b), $a, $b];
    }
}
