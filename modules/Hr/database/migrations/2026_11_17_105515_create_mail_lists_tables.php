<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Hr\Models\MailList;
use Modules\Hr\Support\MailLists\MailListImporter;

/**
 * The organisation's mailing lists (managers@, hope.aleppo@ ...) and their members' addresses.
 * A member is 'active' when already in the group at the mail provider, 'pending' when added here
 * and waiting for whoever holds the provider's control panel to add and confirm them.
 *
 * The lists are loaded once from modules/Hr/database/data/mail-lists.json when none exist yet
 * (never in the test suite, which starts empty); `php artisan mail-lists:load` reloads them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('address', 190)->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('mail_list_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mail_list_id')->constrained('mail_lists')->cascadeOnDelete();
            $table->string('email', 190);
            $table->string('status', 16)->default('active');
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['mail_list_id', 'email']);
            $table->index('email');
            $table->index('status');
        });

        if (! app()->runningUnitTests() && ! MailList::query()->exists()) {
            app(MailListImporter::class)->importFile(MailListImporter::defaultPath());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_list_members');
        Schema::dropIfExists('mail_lists');
    }
};
