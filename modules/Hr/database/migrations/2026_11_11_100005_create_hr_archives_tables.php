<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR archives tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklists', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('delivered_id');
            $table->unsignedBigInteger('receiver_id')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->enum('delivered_status', ['pending', 'delivered'])->default('pending');
            $table->dateTime('returned_at')->nullable();
            $table->enum('returned_status', ['pending', 'received'])->nullable();
            $table->text('notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('order');
            $table->timestamps();
        });

        Schema::create('folders', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
        });

        Schema::create('folder_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('folder_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['folder_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_user');
        Schema::dropIfExists('folders');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklists');
    }
};
