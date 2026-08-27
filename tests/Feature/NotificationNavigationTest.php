<?php

namespace Tests\Feature;

use App\Livewire\Notifications\Dropdown;
use App\Livewire\Notifications\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_an_owned_notification_marks_it_read_before_internal_navigation(): void
    {
        $user = $this->createUser('notification.owner@example.test');
        $url = route('leaves.index', ['leave' => 123]);
        $id = $this->createDatabaseNotification($user, $url);
        $expectedPath = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);

        Livewire::actingAs($user)
            ->test(Dropdown::class)
            ->call('openNotification', $id)
            ->assertRedirect($expectedPath);

        $this->assertNotNull(
            DB::table('notifications')->where('id', $id)->value('read_at'),
        );
    }

    public function test_user_cannot_open_or_mark_another_users_notification(): void
    {
        $owner = $this->createUser('notification.real-owner@example.test');
        $otherUser = $this->createUser('notification.other-user@example.test');
        $id = $this->createDatabaseNotification($owner, route('notifications.index'));

        Livewire::actingAs($otherUser)
            ->test(Dropdown::class)
            ->call('openNotification', $id)
            ->assertNotFound();

        Livewire::actingAs($otherUser)
            ->test(Index::class)
            ->call('markAsRead', $id);

        $this->assertNull(
            DB::table('notifications')->where('id', $id)->value('read_at'),
        );
    }

    public function test_external_and_protocol_relative_notification_urls_never_redirect(): void
    {
        $user = $this->createUser('notification.external-link@example.test');
        $externalId = $this->createDatabaseNotification($user, 'https://evil.example/steal');

        $component = new Dropdown;
        $this->assertNull($component->safeNotificationUrl('https://evil.example/steal'));
        $this->assertNull($component->safeNotificationUrl('//evil.example/steal'));
        $this->assertNull($component->safeNotificationUrl('javascript:alert(1)'));

        Livewire::actingAs($user)
            ->test(Dropdown::class)
            ->assertDontSee('evil.example', false)
            ->call('openNotification', $externalId)
            ->assertNoRedirect();

        $this->assertNotNull(
            DB::table('notifications')->where('id', $externalId)->value('read_at'),
        );
    }

    public function test_mark_all_read_is_consistent_and_includes_notifications_with_links(): void
    {
        $user = $this->createUser('notification.mark-all@example.test');
        $linkedId = $this->createDatabaseNotification($user, route('notifications.index'));
        $plainId = $this->createDatabaseNotification($user);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('markAllAsRead');

        $this->assertNotNull(
            DB::table('notifications')->where('id', $linkedId)->value('read_at'),
        );
        $this->assertNotNull(
            DB::table('notifications')->where('id', $plainId)->value('read_at'),
        );
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    private function createUser(string $email): User
    {
        return User::query()->create([
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function createDatabaseNotification(User $user, ?string $url = null): string
    {
        $id = (string) Str::uuid();
        $data = [
            'title' => ['en' => 'Test notification', 'ar' => 'إشعار اختباري'],
            'body' => ['en' => 'Test body', 'ar' => 'نص اختباري'],
        ];

        if ($url !== null) {
            $data['url'] = $url;
        }

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode($data, JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
