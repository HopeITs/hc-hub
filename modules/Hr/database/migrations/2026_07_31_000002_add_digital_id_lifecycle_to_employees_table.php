<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lifecycle columns for the digital ID token.
 *
 * The token itself is 64 random characters and unguessable, but it behaved as
 * a permanent bearer credential: printed on a card, never expiring, with no
 * way to turn it off if a card was lost, cloned, or confiscated. These columns
 * give it the three states a credential needs — enabled, disabled, rotated —
 * without forcing an expiry that the physical-card workflow may not want.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('digital_id_enabled')->default(true)->after('digital_id_token');
            $table->timestamp('digital_id_rotated_at')->nullable()->after('digital_id_enabled');
            $table->timestamp('digital_id_revoked_at')->nullable()->after('digital_id_rotated_at');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'digital_id_enabled',
                'digital_id_rotated_at',
                'digital_id_revoked_at',
            ]);
        });
    }
};
