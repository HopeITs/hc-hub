<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The columns the HR list screens filter and sort by (status, dates,
     * names, is_active) that no index leads with yet. Every foreign key
     * column already has one (100011: MySQL indexes each FK).
     *
     * @var array<string, list<string|list<string>>>
     */
    private const array INDEXES = [
        'badges' => ['type'],
        'birthday_holiday_choices' => ['status'],
        'checklists' => ['delivered_status', 'returned_status'],
        'contracts' => [['is_active', 'end_date'], 'start_date', 'end_date', 'terminated_at', 'type'],
        'courses' => ['status', 'start_date'],
        'educations' => ['status'],
        'employee_items' => ['status'],
        'employees' => [['is_active', 'first_name_en'], 'first_name_en', 'last_name_en', 'dob'],
        'hiring_campaigns' => ['starts_at', 'ends_at'],
        'holidays' => [['start_date', 'end_date'], 'end_date'],
        'job_levels' => ['grade'],
        'leave_requests' => [['status', 'start_at'], 'start_at', 'end_at'],
        'news' => ['published_at', 'event_starts_at', 'event_date', 'display_until'],
        'positions' => [['is_active', 'title_en']],
        'recruitment_applicants' => ['anonymized_at'],
        'recruitment_interviews' => ['ends_at'],
        'recruitment_job_offer_versions' => ['start_date'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($indexes) {
                foreach ($indexes as $columns) {
                    $table->index($columns);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($indexes) {
                foreach ($indexes as $columns) {
                    $table->dropIndex((array) $columns);
                }
            });
        }
    }
};
