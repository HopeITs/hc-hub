<?php

namespace App\Notifications;

use App\Enums\ApprovalDecision;
use App\Models\RecruitmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Telling the requester what became of their request.
 *
 * The reason an approver gave is often a judgement about a person, a budget or
 * a programme, so it stays inside the system: the database notification the
 * requester reads while signed in carries it, and the mail that travels
 * through somebody else's server does not. The mail says only that a decision
 * was made and where to read it.
 */
class RecruitmentRequestDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RecruitmentRequest $request,
        public int $decision,
        public ?string $comment = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('recruitment.notify.decided_subject', ['number' => $this->request->request_number]))
            ->line(__('recruitment.notify.decided_line', ['number' => $this->request->request_number]))
            ->action(__('recruitment.notify.open_in_system'), $this->url());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        [$en, $ar] = match ($this->decision) {
            ApprovalDecision::Approve => ['was approved', 'تمت الموافقة عليه'],
            ApprovalDecision::Reject => ['was rejected', 'رُفض'],
            default => ['was returned to you for changes', 'أُعيد إليك للتعديل'],
        };

        return [
            'title' => [
                'en' => 'A decision was made on your request',
                'ar' => 'صدر قرار على طلبك',
            ],
            'body' => [
                'en' => "Request {$this->request->request_number} {$en}.",
                'ar' => "الطلب {$this->request->request_number} {$ar}.",
            ],
            // Read while signed in, so it may carry what the mail may not.
            'comment' => $this->comment,
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return route('recruitment.requests.show', $this->request->getKey());
    }
}
