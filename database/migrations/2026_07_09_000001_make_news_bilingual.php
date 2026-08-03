<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'title') && ! Schema::hasColumn('news', 'title_ar')) {
                $table->renameColumn('title', 'title_ar');
            }

            if (Schema::hasColumn('news', 'description') && ! Schema::hasColumn('news', 'description_ar')) {
                $table->renameColumn('description', 'description_ar');
            }
        });

        Schema::table('news', function (Blueprint $table) {
            if (! Schema::hasColumn('news', 'title_en')) {
                $table->string('title_en')->nullable()->after('title_ar');
            }

            if (! Schema::hasColumn('news', 'description_en')) {
                $table->text('description_en')->nullable()->after('description_ar');
            }
        });

        DB::table('news')->whereNull('title_ar')->update(['title_ar' => '']);
        DB::table('news')->whereNull('description_ar')->update(['description_ar' => '']);
        DB::table('news')
            ->whereNull('event_date')
            ->update([
                'event_date' => DB::raw('COALESCE(DATE(published_at), DATE(created_at), CURRENT_DATE)'),
            ]);

        Schema::table('news', function (Blueprint $table) {
            $table->string('title_ar')->nullable(false)->change();
            $table->text('description_ar')->nullable(false)->change();
            $table->date('event_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->date('event_date')->nullable()->change();
            $table->text('description_ar')->nullable()->change();

            if (Schema::hasColumn('news', 'title_en')) {
                $table->dropColumn('title_en');
            }

            if (Schema::hasColumn('news', 'description_en')) {
                $table->dropColumn('description_en');
            }
        });

        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'title_ar') && ! Schema::hasColumn('news', 'title')) {
                $table->renameColumn('title_ar', 'title');
            }

            if (Schema::hasColumn('news', 'description_ar') && ! Schema::hasColumn('news', 'description')) {
                $table->renameColumn('description_ar', 'description');
            }
        });
    }
};
