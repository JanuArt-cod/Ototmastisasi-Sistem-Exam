<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatingAssignment extends Model
{
    protected $fillable = [
        'seating_plan_id',
        'student_id',
        'participant_number_snapshot',
        'student_name_snapshot',
        'class_name_snapshot',
        'grade_level',
        'room_name',
        'room_number',
        'seat_number',
        'desk_number',
        'seat_position',
        'desk_participant_number',
    ];

    protected $casts = [
        'participant_number_snapshot' => 'string',
        'desk_participant_number' => 'string',
        'room_name' => 'string',
        'room_number' => 'integer',
        'seat_number' => 'integer',
        'desk_number' => 'integer',
        'seat_position' => 'integer',
    ];

    public function seatingPlan(): BelongsTo
    {
        return $this->belongsTo(SeatingPlan::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}