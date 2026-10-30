<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fortify's two-factor columns (authenticator app + recovery codes, ported
 * from hc-tracking, task 1.1) and the switch for emailed sign-in codes
 * (App\Actions\Fortify\RedirectIfTwoFactorRequired). Every existing user
 * starts with both off and keeps signing in with the password alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->boolean('email_two_factor_enabled')->default(false)->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'email_two_factor_enabled',
            ]);
        });
    }
};
