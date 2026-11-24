<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_name');
            $table->string('school_name')->nullable();
            $table->string('school_npsn', 30)->nullable();
            $table->text('school_address')->nullable();
            $table->string('exam_title')->nullable();
            $table->string('academic_year', 30)->nullable();
            $table->string('term', 50)->nullable();
            $table->date('exam_date')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('principal_title')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('card_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};