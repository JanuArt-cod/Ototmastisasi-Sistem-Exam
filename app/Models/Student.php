<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = [
        'exam_session_id',
        'student_name',
        'nis',
        'nisn',
        'class_name',
        'participant_number',
        'room_name',
        'photo_path',
        'internal_note',
    ];

    protected $casts = [
        'nis' => 'string',
        'nisn' => 'string',
        'participant_number' => 'string',
        'room_name' => 'string',
    ];

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }
}