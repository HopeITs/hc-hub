<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('whatsapp_work')->nullable()->after('work_phone');
            $table->string('telegram_work')->nullable()->after('whatsapp_work');
            $table->string('facebook')->nullable()->after('telegram_work');
            $table->string('instagram')->nullable()->after('facebook');
            $table->string('linkedin')->nullable()->after('instagram');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_work', 'telegram_work', 'facebook', 'instagram', 'linkedin']);
        });
    }
};
