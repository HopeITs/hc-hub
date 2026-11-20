<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * media.hr_media_id: the hr_media row a file was copied from (roadmap 4.10,
 * D8). NULL for every file uploaded in the hub. Unique, so `hub:move-hr-media
 * --copy` can run again without copying a file twice, and `--verify` knows
 * which original each copy answers to.
 *
 * No foreign key: hr_media is the old app's table and is archived once the
 * move is verified; the copies must outlive it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->unsignedBigInteger('hr_media_id')->nullable()->after('uploaded_by');
            $table->unique('hr_media_id');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropUnique(['hr_media_id']);
            $table->dropColumn('hr_media_id');
        });
    }
};
