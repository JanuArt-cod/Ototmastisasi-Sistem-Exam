<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_sessions', 'signatory_title')) {
                $table->string('signatory_title', 100)
                    ->nullable()
                    ->default('Kepala Sekolah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_sessions', 'signatory_title')) {
                $table->dropColumn('signatory_title');
            }
        });
    }
};