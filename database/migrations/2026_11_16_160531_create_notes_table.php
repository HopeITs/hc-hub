<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The one shared Notes table. A note is about a record (subject_type is a
     * morph-map key, subject_id the row; no foreign key can back it) or, with
     * no subject, a personal note on the author's own Desk. `private` is the
     * author's alone; `context` is visible to whoever can view the subject.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject_type', 50)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('body');
            $table->string('visibility', 20)->default('private');
            $table->string('color', 9)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_type', 'subject_id'], 'notes_subject_index');
            $table->index(['author_id', 'visibility'], 'notes_author_visibility_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
