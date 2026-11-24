<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_sessions', 'foundation_name')) {
                $table->string('foundation_name')->nullable();
            }
            if (! Schema::hasColumn('exam_sessions', 'school_accreditation')) {
                $table->string('school_accreditation')->nullable();
            }
            if (! Schema::hasColumn('exam_sessions', 'issue_city')) {
                $table->string('issue_city')->nullable();
            }
            if (! Schema::hasColumn('exam_sessions', 'signatory_nip')) {
                $table->string('signatory_nip')->nullable();
            }
            if (! Schema::hasColumn('exam_sessions', 'stamp_path')) {
                $table->string('stamp_path')->nullable();
            }
        });

        if (! Schema::hasTable('exam_schedules')) {
            Schema::create('exam_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_session_id')
                    ->constrained('exam_sessions')
                    ->cascadeOnDelete();
                $table->unsignedInteger('day_number')->default(1);
                $table->string('day_date_label');
                $table->string('time_slot');
                $table->string('subject_name');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['exam_session_id', 'day_number', 'sort_order'], 'exam_schedules_session_order_idx');
            });
        }

        Schema::table('card_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('card_settings', 'card_side_mode')) {
                $table->string('card_side_mode', 20)->default('single');
            }
            if (! Schema::hasColumn('card_settings', 'show_foundation_name')) {
                $table->boolean('show_foundation_name')->default(true);
            }
            if (! Schema::hasColumn('card_settings', 'show_accreditation')) {
                $table->boolean('show_accreditation')->default(true);
            }
            if (! Schema::hasColumn('card_settings', 'show_issue_city')) {
                $table->boolean('show_issue_city')->default(true);
            }
            if (! Schema::hasColumn('card_settings', 'show_signatory_nip')) {
                $table->boolean('show_signatory_nip')->default(true);
            }
            if (! Schema::hasColumn('card_settings', 'show_stamp')) {
                $table->boolean('show_stamp')->default(true);
            }
            if (! Schema::hasColumn('card_settings', 'show_back_header')) {
                $table->boolean('show_back_header')->default(true);
            }
            if (! Schema::hasColumn('card_settings', 'back_header_title')) {
                $table->string('back_header_title')->nullable()->default('JADWAL UJIAN');
            }
            if (! Schema::hasColumn('card_settings', 'schedule_font_size_pt')) {
                $table->float('schedule_font_size_pt')->default(6.5);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');

        Schema::table('card_settings', function (Blueprint $table) {
            $columns = [
                'card_side_mode',
                'show_foundation_name',
                'show_accreditation',
                'show_issue_city',
                'show_signatory_nip',
                'show_stamp',
                'show_back_header',
                'back_header_title',
                'schedule_font_size_pt',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('card_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $columns = [
                'foundation_name',
                'school_accreditation',
                'issue_city',
                'signatory_nip',
                'stamp_path',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('exam_sessions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};