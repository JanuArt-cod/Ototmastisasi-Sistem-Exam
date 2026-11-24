<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_sessions', 'signatory_name')) {
                $table->string('signatory_name', 120)->nullable();
            }
        });

        if (Schema::hasColumn('exam_sessions', 'headmaster_name')) {
            DB::table('exam_sessions')
                ->whereNull('signatory_name')
                ->update(['signatory_name' => DB::raw('headmaster_name')]);
        } elseif (Schema::hasColumn('exam_sessions', 'principal_name')) {
            DB::table('exam_sessions')
                ->whereNull('signatory_name')
                ->update(['signatory_name' => DB::raw('principal_name')]);
        }
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('exam_sessions', 'signatory_name')) {
                $table->dropColumn('signatory_name');
            }
        });
    }
};