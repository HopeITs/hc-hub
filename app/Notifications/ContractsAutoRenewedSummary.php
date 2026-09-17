<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ContractsAutoRenewedSummary extends Notification
{
    use Queueable;

    /**
     * @var array<int, int>
     */
    public array $contractIds;

    /**
     * @param  Collection<int, Contract>  $contracts
     */
    public function __construct(Collection $contracts)
    {
        $this->contractIds = $contracts->pluck('id')->values()->all();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title']['ar'])
            ->line($data['body']['ar'])
            ->action('عرض العقود الجديدة', $data['url']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $count = count($this->contractIds);

        return [
            'title' => [
                'en' => 'Contracts renewed',
                'ar' => 'تم تجديد العقود',
            ],
            'body' => [
                'en' => "{$count} contract(s) were renewed successfully today. Click here to view the new contracts.",
                'ar' => "تم تجديد {$count} عقد بنجاح اليوم. اضغط هنا لعرض العقود الجديدة.",
            ],
            'url' => route('contracts.index', [
                'renewed_contracts' => implode(',', $this->contractIds),
            ]),
            'contract_ids' => $this->contractIds,
        ];
    }
}
