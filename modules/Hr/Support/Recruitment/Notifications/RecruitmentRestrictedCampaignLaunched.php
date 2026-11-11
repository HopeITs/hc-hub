<?php

namespace Modules\Hr\Support\Recruitment\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Hr\Models\HiringCampaign;

/** Notifies internal stakeholders when a restricted campaign is launched. */
class RecruitmentRestrictedCampaignLaunched extends Notification
{
    use Queueable;

    public function __construct(public HiringCampaign $campaign) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [InboxChannel::class, 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('hr_recruitment.campaign_ui.notify.restricted_launched_subject'))
            ->line(__('hr_recruitment.campaign_ui.notify.restricted_launched_line', [
                'campaign' => $this->campaign->getKey(),
                'audience' => __('hr_recruitment.campaign_ui.modes.'.$this->campaign->link_mode),
            ]))
            ->action(__('hr_recruitment.campaign_ui.notify.open_campaign'), $this->url());
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => [
                'en' => __('hr_recruitment.campaign_ui.notify.restricted_launched_subject', [], 'en'),
                'ar' => __('hr_recruitment.campaign_ui.notify.restricted_launched_subject', [], 'ar'),
            ],
            'body' => [
                'en' => __('hr_recruitment.campaign_ui.notify.restricted_launched_line', [
                    'campaign' => $this->campaign->getKey(),
                    'audience' => __('hr_recruitment.campaign_ui.modes.'.$this->campaign->link_mode, [], 'en'),
                ], 'en'),
                'ar' => __('hr_recruitment.campaign_ui.notify.restricted_launched_line', [
                    'campaign' => $this->campaign->getKey(),
                    'audience' => __('hr_recruitment.campaign_ui.modes.'.$this->campaign->link_mode, [], 'ar'),
                ], 'ar'),
            ],
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        return route('hr.recruitment.campaigns.show', ['campaignId' => $this->campaign->getKey()]);
    }
}
