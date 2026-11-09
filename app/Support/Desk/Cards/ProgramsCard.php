<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\AnnualPlan;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Programs in card form: every program with its strategy and projects, and
 * whether this year's plan is in.
 */
final class ProgramsCard extends PageCard
{
    public function route(): string
    {
        return 'programs.index';
    }

    public function figures(User $user): array
    {
        $programs = Program::query()->count();
        $planned = AnnualPlan::query()->where('year', now()->year)->distinct()->count('program_id');

        return [
            $this->figure(__('desk-workspace.card.figure.programs'), $programs, 'active'),
            $this->figure(__('desk-workspace.card.figure.planned_this_year'), $planned, 'good'),
            $this->figure(__('desk-workspace.card.figure.no_plan_yet'), max(0, $programs - $planned), $programs > $planned ? 'warn' : 'neutral'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        $planned = AnnualPlan::query()->where('year', now()->year)->pluck('program_id')->flip();

        return Program::query()->with('strategy:id,name')->withCount('projects')->orderByName()->limit(self::RowLimit)->get()
            ->map(fn (Program $program): array => $this->row(
                'pg-'.$program->id,
                (string) $program->name,
                collect([$program->strategy?->name, trans_choice('desk-workspace.card.projects', (int) $program->projects_count, ['count' => (int) $program->projects_count])])->filter()->implode(' · '),
                $planned->has($program->id)
                    ? ['label' => (string) __('desk-workspace.card.planned', ['year' => now()->year]), 'tone' => 'good']
                    : ['label' => (string) __('desk-workspace.card.no_plan', ['year' => now()->year]), 'tone' => 'warn'],
                route('programs.show', $program),
            ));
    }
}
