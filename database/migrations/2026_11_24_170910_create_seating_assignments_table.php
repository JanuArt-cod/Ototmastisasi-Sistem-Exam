<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seating_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seating_plan_id')
                ->constrained('seating_plans')
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();
            $table->string('participant_number_snapshot', 100);
            $table->string('student_name_snapshot', 255);
            $table->string('class_name_snapshot', 100);
            $table->string('grade_level', 30)->nullable();
            $table->unsignedInteger('seat_number');
            $table->unsignedInteger('desk_number');
            $table->unsignedTinyInteger('seat_position'); // 1 = Kursi 1, 2 = Kursi 2
            $table->string('desk_participant_number', 120);
            $table->timestamps();

            $table->unique(['seating_plan_id', 'student_id'], 'sa_plan_student_unique');
            $table->unique(['seating_plan_id', 'seat_number'], 'sa_plan_seat_unique');
            $table->unique(['seating_plan_id', 'desk_number', 'seat_position'], 'sa_plan_desk_pos_unique');
            $table->index(['seating_plan_id', 'class_name_snapshot'], 'sa_plan_class_idx');
            $table->index(['seating_plan_id', 'desk_number'], 'sa_plan_desk_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seating_assignments');
    }
};