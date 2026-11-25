<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seating_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('seating_plans', 'total_rooms')) {
                $table->unsignedInteger('total_rooms')->default(1)->after('total_desks');
            }
        });

        Schema::table('seating_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('seating_assignments', 'room_name')) {
                $table->string('room_name', 100)->default('Ruang 01')->after('grade_level');
            }
            if (! Schema::hasColumn('seating_assignments', 'room_number')) {
                $table->unsignedInteger('room_number')->default(1)->after('room_name');
            }

            $table->dropUnique('sa_plan_seat_unique');
            $table->dropUnique('sa_plan_desk_pos_unique');

            $table->unique(['seating_plan_id', 'room_name', 'seat_number'], 'sa_plan_room_seat_unique');
            $table->unique(['seating_plan_id', 'room_name', 'desk_number', 'seat_position'], 'sa_plan_room_desk_pos_unique');
            $table->index(['seating_plan_id', 'room_number', 'seat_number'], 'sa_plan_room_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('seating_assignments', function (Blueprint $table) {
            $table->dropIndex('sa_plan_room_order_idx');
            $table->dropUnique('sa_plan_room_seat_unique');
            $table->dropUnique('sa_plan_room_desk_pos_unique');

            $table->dropColumn(['room_name', 'room_number']);

            $table->unique(['seating_plan_id', 'seat_number'], 'sa_plan_seat_unique');
            $table->unique(['seating_plan_id', 'desk_number', 'seat_position'], 'sa_plan_desk_pos_unique');
        });

        Schema::table('seating_plans', function (Blueprint $table) {
            if (Schema::hasColumn('seating_plans', 'total_rooms')) {
                $table->dropColumn('total_rooms');
            }
        });
    }
};