<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Assistant;

use App\Jobs\RunAiPrompt;
use Modules\Core\Models\User;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Support\Desk\HrViewer;
use Modules\Hr\Support\Desk\UpcomingHolidays;

/**
 * The HR assistant on the hub's AI gateway (the old Assistant/Chat, cut to
 * questions and answers). A question goes to the "ai" queue through
 * RunAiPrompt with the asker's own facts attached; the page collects the
 * answer by key. The old app's tools (drafting and sending leave requests,
 * the policy library synced from Drive, team look-ups) are not ported.
 */
final class HrAssistant
{
    public const string Feature = 'hr-assistant';

    public const int MaxQuestion = 1000;

    /**
     * Whether an AI provider is configured at all.
     */
    public function isAvailable(): bool
    {
        return filled(config('ai.default')) && filled(config('ai.models.default'));
    }

    /**
     * Queue a question and return the key its answer arrives under.
     */
    public function ask(User $user, string $question): string
    {
        $prompt = $this->factsFor($user)."\n\nQuestion:\n".mb_substr(trim($question), 0, self::MaxQuestion);

        return RunAiPrompt::start(new HrAssistantAgent, $prompt, self::Feature, $user, 'fast');
    }

    /**
     * The asker's own facts, as plain lines.
     */
    public function factsFor(User $user): string
    {
        $viewer = HrViewer::for($user);
        $contract = $viewer->contract();
        $hours = (float) ($contract?->work_hour_per_day ?? 0) ?: 8.0;
        $lines = ['Today: '.now()->toDateString()];

        if ($contract !== null) {
            $balances = LeaveBalance::query()
                ->select(['id', 'contract_id', 'type_id', 'deserved', 'used'])
                ->where('contract_id', $contract->id)
                ->with('leaveType:id,name_en,name_ar')
                ->get();

            foreach ($balances as $balance) {
                $lines[] = sprintf('Leave balance "%s": %s of %s days left (%s hours a day).',
                    (string) ($balance->leaveType?->name_en ?? '—'),
                    round(max(0.0, $balance->remainingHours()) / $hours, 1),
                    round($balance->deserved / $hours, 1),
                    $hours,
                );
            }
        } else {
            $lines[] = 'No running contract on record.';
        }

        foreach (app(UpcomingHolidays::class)->for($viewer->programId(), $viewer->cityId(), 90) as $holiday) {
            $lines[] = sprintf('Holiday "%s": %s to %s.', $holiday['name'], $holiday['from']->toDateString(), $holiday['to']->toDateString());
        }

        return "Facts:\n".implode("\n", $lines);
    }
}
