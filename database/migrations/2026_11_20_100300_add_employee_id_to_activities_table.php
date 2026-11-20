<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * activities.employee_id: whom an entry is about (roadmap 4.7, D6), beside
 * actor_id (who did it) and the subject (the record). Nullable: most entries
 * are about no one in particular.
 *
 * The backfill reads only what each row already says, in batches, and can run
 * again safely: an entry whose subject is an employee is about that employee,
 * and an entry that recorded properties.employee_id is about that one. Ids
 * with no employee row are left empty; nothing is matched on a name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->foreignId('employee_id')->nullable()->after('subject_id')->constrained('employees')->nullOnDelete();
            $table->index(['employee_id', 'id']);
        });

        DB::table('activities')->whereNull('employee_id')->orderBy('id')->chunkById(500, function ($activities): void {
            $wanted = [];

            foreach ($activities as $activity) {
                $about = $activity->subject_type === 'employee'
                    ? (int) $activity->subject_id
                    : (json_decode((string) $activity->properties, true)['employee_id'] ?? null);

                if (is_int($about) && $about > 0) {
                    $wanted[(int) $activity->id] = $about;
                }
            }

            if ($wanted === []) {
                return;
            }

            $existing = DB::table('employees')->whereIn('id', array_unique(array_values($wanted)))->pluck('id')->flip();

            DB::transaction(function () use ($wanted, $existing): void {
                foreach ($wanted as $activityId => $employeeId) {
                    if ($existing->has($employeeId)) {
                        DB::table('activities')->where('id', $activityId)->update(['employee_id' => $employeeId]);
                    }
                }
            });
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropForeign(['employee_id']);
            $table->dropIndex(['employee_id', 'id']);
            $table->dropColumn('employee_id');
        });
    }
};
