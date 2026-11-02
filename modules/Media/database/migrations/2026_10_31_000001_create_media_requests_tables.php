<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Media requests (ported from media-request): what the Media team (the
 * Communication department) receives from everyone else — design (001),
 * photography (002), videography (003) and ready files from HC_PACK (000).
 *
 * People are HR employees (employees.id), places and programs the hub's
 * cities and programs: the standalone app's own employees / programs /
 * cities / program_managers tables are not carried over. Who is on the team
 * and who manages a program is read from HR contracts (MediaTeam).
 *
 * Every open status has exactly one owner (MediaRequestStatus::owner()), and
 * status_changed_at is what the "late" flag is measured from. The history
 * lives in activity_log (log name "media-requests"); comments are the
 * conversation between requester and team.
 *
 * Column names follow the Core naming standard (is_* booleans, *_on dates,
 * *_at timestamps); against media-request: needed_by → needed_on,
 * planned_for → planned_on, internal_ok → is_internal_ok,
 * needs_program_approval → is_program_approval_needed, and in the assistant
 * log understood → is_understood, corrected → is_corrected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique()->comment('MR-{service}-{yy}-{0001}');
            $table->foreignId('requester_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('service', 3)->comment('001 design, 002 photo, 003 video, 000 HC_PACK');
            $table->string('type', 20);
            $table->string('item_code', 40)->comment('Certificate, ID, Photos… part of the archive name');
            $table->string('title', 120)->comment('Short name typed after the automatic prefix');
            $table->text('description')->nullable();
            $table->json('answers')->nullable()->comment('Answers to the type-specific questions');
            $table->json('pack_files')->nullable()->comment('HC_PACK paths for service 000');
            $table->date('needed_on')->nullable();
            $table->text('rush_reason')->nullable();
            $table->string('delivery_channel', 20)->nullable();
            $table->string('delivery_contact', 190)->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('status_changed_at')->nullable();
            $table->string('return_to', 20)->nullable()->comment('Status to resume after needs_info / on_hold');
            $table->string('held_by', 20)->nullable()->comment('Which side put it on hold');
            $table->boolean('is_program_approval_needed')->default(true);
            $table->foreignId('program_approver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('program_approved_at')->nullable();
            $table->foreignId('media_approver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('media_approved_at')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('priority', 10)->nullable();
            $table->date('planned_on')->nullable();
            $table->boolean('is_internal_ok')->default(false)->comment('After the first media-manager check, revisions go straight to the requester');
            $table->unsignedSmallInteger('revisions')->default(0);
            $table->string('draft_link', 500)->nullable();
            $table->string('result_link', 500)->nullable();
            $table->string('archive_link', 500)->nullable();
            $table->string('archive_name', 160)->nullable();
            $table->timestamp('last_nudged_at')->nullable();
            $table->date('last_reminded_on')->nullable()->comment('media:remind sends at most one reminder a day');
            $table->timestamp('delivered_at')->nullable();
            $table->string('imported_from', 40)->nullable()->unique()->comment('e.g. "sheet:123" for the old Google sheet');
            $table->timestamps();

            $table->index(['service', 'status']);
            $table->index(['program_id', 'status']);
            $table->index(['assignee_id', 'status']);
        });

        Schema::create('media_request_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // What the media manager taught the request assistant: a phrase and what it means.
        Schema::create('media_assistant_lessons', function (Blueprint $table) {
            $table->id();
            $table->string('phrase', 120)->comment('As people write it, e.g. "رول أب" or "كرت معايدة"');
            $table->json('keywords')->nullable()->comment('More words for the same thing');
            $table->string('meaning', 12)->default('request')->comment('request | generator | pack | reply');
            $table->string('service', 3)->nullable();
            $table->string('type', 20)->nullable();
            $table->string('format', 20)->nullable()->comment('Design kind for a new design: poster, cert…');
            $table->string('tool', 30)->nullable();
            $table->string('pack_query', 120)->nullable();
            $table->string('reply', 500)->nullable();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete()->comment('Who taught it');
            $table->timestamps();
        });

        // What people type to the request assistant, what it understood, and what they finally asked for.
        Schema::create('media_assistant_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('text');
            $table->boolean('is_understood')->default(false);
            $table->string('service', 3)->nullable();
            $table->string('type', 20)->nullable();
            $table->json('answers')->nullable();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->date('needed_on')->nullable();
            $table->json('asked')->nullable()->comment('Follow-up questions it still had to ask');
            $table->foreignId('media_request_id')->nullable()->constrained('media_requests')->nullOnDelete();
            $table->string('final_service', 3)->nullable();
            $table->string('final_type', 20)->nullable();
            $table->boolean('is_corrected')->default(false)->comment('The person picked a different service or type');
            $table->foreignId('lesson_id')->nullable()->constrained('media_assistant_lessons')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_understood', 'created_at']);
        });

        // Each person's own media home page: cards, order, sizes, pins and private notes.
        Schema::create('media_dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('layout')->nullable()->comment('[{key, size: s|m|l, pinned, hidden}] in display order');
            $table->text('notes')->nullable()->comment('Private notes; only the owner sees them');
            $table->timestamps();
        });

        // Each person's proofreading checks; private to them.
        Schema::create('media_proofreads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 190)->default('text');
            $table->text('original');
            $table->text('corrected');
            $table->json('result')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_proofreads');
        Schema::dropIfExists('media_dashboards');
        Schema::dropIfExists('media_assistant_logs');
        Schema::dropIfExists('media_assistant_lessons');
        Schema::dropIfExists('media_request_comments');
        Schema::dropIfExists('media_requests');
    }
};
