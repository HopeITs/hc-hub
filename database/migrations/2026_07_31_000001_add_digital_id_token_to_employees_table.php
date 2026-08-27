<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('digital_id_token', 64)->nullable()->unique();
        });

        DB::table('employees')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($employees): void {
                foreach ($employees as $employee) {
                    DB::table('employees')
                        ->where('id', $employee->id)
                        ->update(['digital_id_token' => Str::random(64)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['digital_id_token']);
            $table->dropColumn('digital_id_token');
        });
    }
};
