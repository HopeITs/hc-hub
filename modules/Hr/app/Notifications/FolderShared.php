<?php

namespace Modules\Hr\Notifications;

use App\Notifications\Concerns\RoutesToInbox;
use App\Notifications\InboxNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Hr\Models\Folder;

class FolderShared extends Notification implements InboxNotification
{
    use Queueable;
    use RoutesToInbox;

    public function __construct(public Folder $folder, public string $creatorName) {}

    public function inboxModule(): string
    {
        return 'archive';
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => [
                'en' => "New Folder Shared: {$this->folder->name}",
                'ar' => "تمت مشاركة مجلد جديد: {$this->folder->name}",
            ],

            'body' => [
                'en' => "{$this->creatorName} shared the folder '{$this->folder->name}' with you. You can now access it in Shared Folders.",
                'ar' => "شارك {$this->creatorName} المجلد '{$this->folder->name}' معك. يمكنك الوصول إليه الآن من المجلدات المشتركة.",
            ],
            'url' => route('shared-folders.index', ['folderId' => $this->folder->id]),
        ];
    }
}
