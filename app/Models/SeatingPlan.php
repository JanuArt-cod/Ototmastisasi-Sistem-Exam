<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatingPlan extends Model
{
    protected $fillable = [
        'exam_session_id',
        'version_number',
        'status',
        'selection_mode',
        'selected_classes_json',
        'seats_per_desk',
        'starting_seat_number',
        'total_students',
        'total_desks',
        'total_rooms',
        'cross_grade_pairs',
        'same_grade_pairs',
        'single_seat_desks',
        'warnings_json',
        'layout_settings_json',
        'generated_at',
        'finalized_at',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'seats_per_desk' => 'integer',
        'starting_seat_number' => 'integer',
        'total_students' => 'integer',
        'total_desks' => 'integer',
        'total_rooms' => 'integer',
        'cross_grade_pairs' => 'integer',
        'same_grade_pairs' => 'integer',
        'single_seat_desks' => 'integer',
        'selected_classes_json' => 'array',
        'warnings_json' => 'array',
        'layout_settings_json' => 'array',
        'generated_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SeatingAssignment::class)
            ->orderBy('room_number')
            ->orderBy('seat_number');
    }

    public static function defaultLayoutSettings(): array
    {
        return [
            'floor_columns' => 3,
            'floor_rows_per_page' => 5,
            'floor_orientation' => 'portrait',
            'floor_numbering_order' => 'row_first',
            'desk_card_width_mm' => 70.0,
            'desk_card_height_mm' => 35.0,
            'desk_paper_size' => 'A4',
            'desk_paper_orientation' => 'portrait',
            'desk_margin_top_mm' => 8.0,
            'desk_margin_right_mm' => 8.0,
            'desk_margin_bottom_mm' => 8.0,
            'desk_margin_left_mm' => 8.0,
            'desk_gap_x_mm' => 3.0,
            'desk_gap_y_mm' => 3.0,
            'desk_cards_per_row' => 2,
            'desk_cards_per_column' => 7,
            'desk_show_cut_lines' => true,
        ];
    }

    public function resolvedLayoutSettings(): array
    {
        return array_merge(
            self::defaultLayoutSettings(),
            is_array($this->layout_settings_json) ? $this->layout_settings_json : []
        );
    }
}