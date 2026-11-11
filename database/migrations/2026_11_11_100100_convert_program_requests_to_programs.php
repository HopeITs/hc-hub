<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Program requests are gone: a proposal location now creates its new
     * Core program directly (CreateLocationPlaces). A request still pending
     * becomes a real program (English name as the name, Arabic as
     * description_ar, as CreateLocationPlaces does) and its row points at it,
     * unless that row already holds the same program for the city. Then the
     * table is dropped.
     */
    public function up(): void
    {
        if (! Schema::hasTable('program_requests')) {
            return;
        }

        DB::transaction(function (): void {
            $requests = DB::table('program_requests')->where('status', 'pending')->orderBy('id')->get();

            foreach ($requests as $request) {
                $programId = DB::table('programs')->insertGetId([
                    'name' => $request->name_en,
                    'description_ar' => $request->name_ar,
                    'slug' => $this->uniqueSlug((string) $request->name_en),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $row = DB::table('proposal_locations')->where('id', $request->proposal_location_id)->first();

                if ($row === null || $row->program_id !== null) {
                    continue;
                }

                $taken = DB::table('proposal_locations')
                    ->where('proposal_id', $row->proposal_id)
                    ->where('city_id', $row->city_id)
                    ->where('program_id', $programId)
                    ->exists();

                if (! $taken) {
                    DB::table('proposal_locations')->where('id', $row->id)->update(['program_id' => $programId, 'updated_at' => now()]);
                }
            }
        });

        Schema::drop('program_requests');
    }

    /**
     * The table comes back empty; converted programs stay.
     */
    public function down(): void
    {
        Schema::create('program_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_location_id')->unique()->constrained('proposal_locations')->cascadeOnDelete();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('hr_program_id')->nullable()->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::upper(Str::slug($name)) ?: 'PROGRAM';
        $slug = $base;
        $suffix = 2;

        while (DB::table('programs')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
};
