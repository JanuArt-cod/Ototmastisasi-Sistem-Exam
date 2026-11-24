<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('card_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('card_settings', 'border_color')) {
                $table->string('border_color', 20)->default('#1e293b')->after('card_height_mm');
            }
            if (! Schema::hasColumn('card_settings', 'header_bg_color')) {
                $table->string('header_bg_color', 20)->default('#f8fafc')->after('border_color');
            }
            if (! Schema::hasColumn('card_settings', 'accent_color')) {
                $table->string('accent_color', 20)->default('#0f172a')->after('header_bg_color');
            }
            if (! Schema::hasColumn('card_settings', 'show_logo')) {
                $table->boolean('show_logo')->default(true)->after('accent_color');
            }
            if (! Schema::hasColumn('card_settings', 'show_school_address')) {
                $table->boolean('show_school_address')->default(true)->after('show_logo');
            }
            if (! Schema::hasColumn('card_settings', 'show_exam_date')) {
                $table->boolean('show_exam_date')->default(false)->after('show_school_address');
            }
            if (! Schema::hasColumn('card_settings', 'show_participant_number')) {
                $table->boolean('show_participant_number')->default(true)->after('show_exam_date');
            }
            if (! Schema::hasColumn('card_settings', 'show_nis')) {
                $table->boolean('show_nis')->default(true)->after('show_participant_number');
            }
            if (! Schema::hasColumn('card_settings', 'show_nisn')) {
                $table->boolean('show_nisn')->default(true)->after('show_nis');
            }
            if (! Schema::hasColumn('card_settings', 'show_class_name')) {
                $table->boolean('show_class_name')->default(true)->after('show_nisn');
            }
            if (! Schema::hasColumn('card_settings', 'show_room_name')) {
                $table->boolean('show_room_name')->default(true)->after('show_class_name');
            }
            if (! Schema::hasColumn('card_settings', 'show_photo')) {
                $table->boolean('show_photo')->default(true)->after('show_room_name');
            }
            if (! Schema::hasColumn('card_settings', 'photo_position')) {
                $table->string('photo_position', 10)->default('right')->after('show_photo');
            }
            if (! Schema::hasColumn('card_settings', 'photo_width_mm')) {
                $table->decimal('photo_width_mm', 5, 2)->default(17.00)->after('photo_position');
            }
            if (! Schema::hasColumn('card_settings', 'photo_height_mm')) {
                $table->decimal('photo_height_mm', 5, 2)->default(22.00)->after('photo_width_mm');
            }
            if (! Schema::hasColumn('card_settings', 'show_signature')) {
                $table->boolean('show_signature')->default(true)->after('photo_height_mm');
            }
            if (! Schema::hasColumn('card_settings', 'show_card_note')) {
                $table->boolean('show_card_note')->default(true)->after('show_signature');
            }
            if (! Schema::hasColumn('card_settings', 'custom_paper_width_mm')) {
                $table->decimal('custom_paper_width_mm', 6, 2)->nullable()->after('print_orientation');
            }
            if (! Schema::hasColumn('card_settings', 'custom_paper_height_mm')) {
                $table->decimal('custom_paper_height_mm', 6, 2)->nullable()->after('custom_paper_width_mm');
            }
            if (! Schema::hasColumn('card_settings', 'margin_top_mm')) {
                $table->decimal('margin_top_mm', 5, 2)->default(10.00)->after('custom_paper_height_mm');
            }
            if (! Schema::hasColumn('card_settings', 'margin_right_mm')) {
                $table->decimal('margin_right_mm', 5, 2)->default(10.00)->after('margin_top_mm');
            }
            if (! Schema::hasColumn('card_settings', 'margin_bottom_mm')) {
                $table->decimal('margin_bottom_mm', 5, 2)->default(10.00)->after('margin_right_mm');
            }
            if (! Schema::hasColumn('card_settings', 'margin_left_mm')) {
                $table->decimal('margin_left_mm', 5, 2)->default(10.00)->after('margin_bottom_mm');
            }
            if (! Schema::hasColumn('card_settings', 'gap_x_mm')) {
                $table->decimal('gap_x_mm', 5, 2)->default(4.00)->after('margin_left_mm');
            }
            if (! Schema::hasColumn('card_settings', 'gap_y_mm')) {
                $table->decimal('gap_y_mm', 5, 2)->default(4.00)->after('gap_x_mm');
            }
            if (! Schema::hasColumn('card_settings', 'cards_per_row')) {
                $table->unsignedTinyInteger('cards_per_row')->default(0)->after('gap_y_mm');
            }
            if (! Schema::hasColumn('card_settings', 'cards_per_column')) {
                $table->unsignedTinyInteger('cards_per_column')->default(0)->after('cards_per_row');
            }
            if (! Schema::hasColumn('card_settings', 'show_cut_lines')) {
                $table->boolean('show_cut_lines')->default(true)->after('cards_per_column');
            }
        });
    }

    public function down(): void
    {
        // Biarkan kolom tetap aman saat rollback parsial
    }
};