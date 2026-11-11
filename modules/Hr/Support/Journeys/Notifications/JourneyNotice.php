<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Journeys\Notifications;

use Illuminate\Notifications\Notification;
use Modules\Hr\Support\Recruitment\Notifications\InboxChannel;

/**
 * One journey notice to the hub inbox (the old OrientationOverdue,
 * LastContractEnded and ContractsExpiringAutoRenewWarning): a title and a
 * body from lang/{en,ar}/hr_journeys.php `notify.<kind>`, in both languages,
 * and the page it opens. In-app only, as in the HR app; never a pay figure.
 */
class JourneyNotice extends Notification
{
    /**
     * @param  array<string, mixed>  $params  placeholders for the body; a value given as ['en' => ..., 'ar' => ...] is used per language
     */
    public function __construct(
        public string $kind,
        public array $params,
        public string $url,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [InboxChannel::class];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => [
                'en' => __("hr_journeys.notify.{$this->kind}.title", [], 'en'),
                'ar' => __("hr_journeys.notify.{$this->kind}.title", [], 'ar'),
            ],
            'body' => [
                'en' => __("hr_journeys.notify.{$this->kind}.body", $this->paramsFor('en'), 'en'),
                'ar' => __("hr_journeys.notify.{$this->kind}.body", $this->paramsFor('ar'), 'ar'),
            ],
            'url' => $this->url,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function paramsFor(string $locale): array
    {
        return array_map(
            fn (mixed $value): string => is_array($value) ? (string) ($value[$locale] ?? reset($value)) : (string) $value,
            $this->params,
        );
    }
}
