<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\EvidenceType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Evidence types in card form: how many are offered, how many files use
 * them, and the list in its managed order with each type's use.
 */
final class EvidenceTypesCard extends PageCard
{
    public function route(): string
    {
        return 'admin.evidence-types.index';
    }

    public function figures(User $user): array
    {
        $active = EvidenceType::query()->active()->count();

        return [
            $this->figure(__('desk-workspace.card.figure.offered'), $active, 'good'),
            $this->figure(__('desk-workspace.card.figure.in_use'), DB::table('monthly_report_files')->distinct()->count('evidence_type_id')),
            $this->figure(__('desk-workspace.card.figure.inactive'), EvidenceType::query()->count() - $active),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        $uses = DB::table('monthly_report_files')->selectRaw('evidence_type_id, count(*) as uses')->groupBy('evidence_type_id')->pluck('uses', 'evidence_type_id');

        return EvidenceType::query()->ordered()->get()->map(fn (EvidenceType $type): array => $this->row(
            'et-'.$type->id,
            $type->localizedName(),
            trans_choice('desk-workspace.card.files', (int) ($uses[$type->id] ?? 0), ['count' => (int) ($uses[$type->id] ?? 0)]),
            $this->activeBadge($type->is_active),
            route($this->route()),
        ));
    }
}
