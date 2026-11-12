<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Contracts\PositionSeats;

/**
 * Repair: recompute `positions.exist_emp` from the contracts that hold a seat
 * (active and not a draft, the rule PositionSeats keeps it to). For a counter
 * that drifted: an import, a write outside the contract Actions, a terminated
 * contract whose seat was never freed. Idempotent: a second run reports
 * nothing. Never touches max_emp. Run by hand; it is not scheduled.
 */
class RecountPositionSeatsCommand extends Command
{
    protected $signature = 'positions:recount-seats {--dry-run : Report the differences without writing them}';

    protected $description = 'Recompute exist_emp from active non-draft contracts and print every position that was off';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;

        Position::query()->select(['id'])->chunkById(200, function (Collection $positions) use ($dryRun, &$changed): void {
            foreach ($positions as $row) {
                $diff = DB::transaction(fn (): ?array => PositionSeats::recount((int) $row->id, ! $dryRun));

                if ($diff !== null) {
                    $changed++;
                    $this->line("Position #{$row->id}: exist_emp {$diff['stored']} -> {$diff['actual']} (max_emp {$diff['max']})");
                }
            }
        });

        $this->components->info($dryRun
            ? "Dry run: {$changed} position(s) would change. Nothing was written."
            : "{$changed} position(s) corrected.");

        return self::SUCCESS;
    }
}
