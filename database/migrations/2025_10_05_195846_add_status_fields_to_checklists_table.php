<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('checklists', function (Blueprint $table) {
            if (!Schema::hasColumn('checklists', 'delivered_status')) {
                $table->enum('delivered_status', ['pending', 'delivered'])->default('pending');
            } else {
                $table->enum('delivered_status', ['pending', 'delivered'])->default('pending')->change();
            }

            if (!Schema::hasColumn('checklists', 'returned_status')) {
                $table->enum('returned_status', ['pending', 'received'])->nullable()->after('returned_at');
            }

            if (!Schema::hasColumn('checklists', 'receiver_id')) {
                $table->unsignedBigInteger('receiver_id')->nullable()->after('delivered_id');
            }

            if (!Schema::hasColumn('checklists', 'return_notes')) {
                $table->text('return_notes')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checklists', function (Blueprint $table) {
            if (Schema::hasColumn('checklists', 'returned_status')) {
                $table->dropColumn('returned_status');
            }
            if (Schema::hasColumn('checklists', 'receiver_id')) {
                $table->dropColumn('receiver_id');
            }
            if (Schema::hasColumn('checklists', 'return_notes')) {
                $table->dropColumn('return_notes');
            }
        });
    }
};
