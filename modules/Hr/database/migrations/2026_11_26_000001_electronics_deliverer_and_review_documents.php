<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding journey follow-up:
 *
 *  - the electronics are handed over by IT of the volunteer's city; when the
 *    city has several IT people HR picks one per volunteer, kept here;
 *  - policy_documents.onboarding_kind: "policy" is read and agreed, "review" is
 *    a document made by another team (the Media guideline, by the Media Team)
 *    that the volunteer only has to review. Same acknowledgement table.
 *
 * Additive. Existing documents stay "policy". Rollback drops both columns.
 */
return new class extends Migration
{
    private const FOREIGN_KEY = 'onb_journeys_electronics_deliverer_fk';

    public function up(): void
    {
        if (! Schema::hasColumn('onboarding_journeys', 'electronics_deliverer_id')) {
            Schema::table('onboarding_journeys', function (Blueprint $table): void {
                $table->unsignedBigInteger('electronics_deliverer_id')->nullable()->after('card_handed_over_by');
            });
        }

        if (! collect(Schema::getForeignKeys('onboarding_journeys'))->contains(fn (array $key): bool => $key['name'] === self::FOREIGN_KEY)) {
            Schema::table('onboarding_journeys', function (Blueprint $table): void {
                $table->foreign('electronics_deliverer_id', self::FOREIGN_KEY)->references('id')->on('employees')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('policy_documents', 'onboarding_kind')) {
            Schema::table('policy_documents', function (Blueprint $table): void {
                $table->string('onboarding_kind', 10)->default('policy')->after('required_for_onboarding');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('policy_documents', 'onboarding_kind')) {
            Schema::table('policy_documents', function (Blueprint $table): void {
                $table->dropColumn('onboarding_kind');
            });
        }

        if (collect(Schema::getForeignKeys('onboarding_journeys'))->contains(fn (array $key): bool => $key['name'] === self::FOREIGN_KEY)) {
            Schema::table('onboarding_journeys', fn (Blueprint $table) => $table->dropForeign(self::FOREIGN_KEY));
        }

        if (Schema::hasColumn('onboarding_journeys', 'electronics_deliverer_id')) {
            Schema::table('onboarding_journeys', fn (Blueprint $table) => $table->dropColumn('electronics_deliverer_id'));
        }
    }
};
