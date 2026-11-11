<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The HR program columns, so programs becomes the one shared (Core)
     * program table under the HR ids it already mirrors. `code` is the
     * numeric code (not unique in HR), `slug` the unique mnemonic (HC, SZ...).
     * name/code/slug stay nullable until tracking stops reading them from HR
     * (step A2); `hub:import-hr` fills them.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->text('description_en')->nullable()->after('name');
            $table->text('description_ar')->nullable()->after('description_en');
            $table->string('code')->nullable()->after('description_ar');
            $table->string('slug')->nullable()->unique()->after('code');
            $table->dateTime('start_date')->nullable()->after('slug');
            $table->dateTime('end_date')->nullable()->after('start_date');
            $table->boolean('is_active')->default(true)->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['name', 'description_en', 'description_ar', 'code', 'slug', 'start_date', 'end_date', 'is_active']);
        });
    }
};
