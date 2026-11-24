<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')
                ->constrained('exam_sessions')
                ->cascadeOnDelete();
            $table->string('student_name');
            $table->string('nis', 50)->nullable();
            $table->string('nisn', 50)->nullable();
            $table->string('class_name', 100);
            $table->string('participant_number', 100)->nullable();
            $table->string('room_name', 100)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamps();

            $table->index(['exam_session_id', 'class_name']);
            $table->index(['exam_session_id', 'participant_number']);
            $table->index(['exam_session_id', 'student_name']);
            $table->index(['exam_session_id', 'nis']);
            $table->index(['exam_session_id', 'nisn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};