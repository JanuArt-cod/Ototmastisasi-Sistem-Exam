<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')
                ->unique()
                ->constrained('exam_sessions')
                ->cascadeOnDelete();
            $table->decimal('card_width_mm', 6, 2)->default(90.00);
            $table->decimal('card_height_mm', 6, 2)->default(55.00);
            $table->string('card_orientation', 20)->default('landscape');
            $table->json('visible_fields')->nullable();
            $table->json('typography_settings')->nullable();
            $table->json('color_settings')->nullable();
            $table->string('photo_position', 20)->default('right');
            $table->string('print_paper_size', 20)->default('A4');
            $table->string('print_orientation', 20)->default('portrait');
            $table->unsignedTinyInteger('rows_per_page')->default(4);
            $table->unsignedTinyInteger('columns_per_page')->default(2);
            $table->decimal('page_margin_mm', 6, 2)->default(10.00);
            $table->decimal('card_gap_mm', 6, 2)->default(5.00);
            $table->boolean('show_cut_guides')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_settings');
    }
};