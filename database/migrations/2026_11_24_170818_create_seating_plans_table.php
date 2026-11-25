<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seating_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')
                ->constrained('exam_sessions')
                ->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('status', 30)->default('draft'); // draft, finalized, archived
            $table->string('selection_mode', 30)->default('all'); // all, classes
            $table->json('selected_classes_json')->nullable();
            $table->unsignedTinyInteger('seats_per_desk')->default(2);
            $table->unsignedInteger('starting_seat_number')->default(1);
            $table->unsignedInteger('total_students')->default(0);
            $table->unsignedInteger('total_desks')->default(0);
            $table->unsignedInteger('cross_grade_pairs')->default(0);
            $table->unsignedInteger('same_grade_pairs')->default(0);
            $table->unsignedInteger('single_seat_desks')->default(0);
            $table->json('warnings_json')->nullable();
            $table->json('layout_settings_json')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_session_id', 'version_number'], 'seating_plans_session_version_unique');
            $table->index(['exam_session_id', 'status'], 'seating_plans_session_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seating_plans');
    }
};