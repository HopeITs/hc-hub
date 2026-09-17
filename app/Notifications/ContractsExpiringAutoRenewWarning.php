<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ContractsExpiringAutoRenewWarning extends Notification
{
    use Queueable;

    /**
     * @var array<int, array{id: int, employee_name_en: string, employee_name_ar: string, end_date: string}>
     */
    public array $contracts;

    /**
     * @param  Collection<int, Contract>  $contracts
     * @param  array<int, string>  $hrEmails
     */
    public function __construct(Collection $contracts, public array $hrEmails)
    {
        $this->contracts = $contracts
            ->map(fn (Contract $contract): array => [
                'id' => $contract->id,
                'employee_name_en' => $contract->employee?->nameForLocale('en') ?? '',
                'employee_name_ar' => $contract->employee?->nameForLocale('ar') ?? '',
                'end_date' => $contract->end_date?->toDateString() ?? '',
            ])
            ->values()
            ->all();
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
            ->line($this->contractList('ar'))
            ->action('إرسال بريد إلى الموارد البشرية', $data['url']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $count = count($this->contracts);

        return [
            'title' => [
                'en' => $count === 1 ? 'Contract expiring next week' : 'Contracts expiring next week',
                'ar' => $count === 1 ? 'عقد ينتهي الأسبوع القادم' : 'عقود تنتهي الأسبوع القادم',
            ],
            'body' => [
                'en' => $count === 1
                    ? "{$this->contracts[0]['employee_name_en']}'s contract ends next week. If you want to disable automatic renewal, please email your Governorate HR."
                    : "{$count} contracts end next week. If needed, please email Governorate HR the list of people you do not want renewed and the reason.",
                'ar' => $count === 1
                    ? "المتطوع {$this->contracts[0]['employee_name_ar']} ينتهي عقده في الاسبوع القادم في حال رغبتك بإلغاء خدمة التجديد التلقائي يرجى إعلام مسؤول الموارد البشرية ضمن محافظتك"
                    : "يوجد {$count} عقود ستنتهي خلال الاسبوع القادم في حال رغبتك بذلك يرجى ارسال قائمة الافراد الذين ترغب بعدم التجديد لهم مع ذكر السبب",
            ],
            'url' => $this->mailtoUrl(),
            'contracts' => $this->contracts,
        ];
    }

    private function contractList(string $locale): string
    {
        return collect($this->contracts)
            ->map(fn (array $contract): string => sprintf(
                '- %s (%s)',
                $contract["employee_name_{$locale}"] ?: $contract['employee_name_en'],
                $contract['end_date'],
            ))
            ->implode("\n");
    }

    private function mailtoUrl(): string
    {
        $subject = count($this->contracts) === 1
            ? 'طلب تعديل التجديد التلقائي'
            : 'قائمة عقود لا نرغب بتجديدها تلقائياً';

        $body = $this->contractList('ar');
        $to = implode(',', array_unique(array_filter($this->hrEmails)));

        return 'mailto:'.$to.'?subject='.rawurlencode($subject).'&body='.rawurlencode($body);
    }
}
