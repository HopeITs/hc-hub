<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('program_manager_id')->nullable()->after('is_active')->constrained('employees')->onDelete('set null');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->foreignId('project_manager_id')->nullable()->after('is_active')->constrained('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['program_manager_id']);
            $table->dropColumn('program_manager_id');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->dropForeign(['project_manager_id']);
            $table->dropColumn('project_manager_id');
        });
    }
};
