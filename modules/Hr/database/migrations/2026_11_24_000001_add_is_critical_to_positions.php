<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A position HR marks as critical: it must never stay without a holder. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->boolean('is_critical')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropColumn('is_critical');
        });
    }
};
