<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSchedule extends Model
{
    protected $fillable = [
        'exam_session_id',
        'day_number',
        'day_date_label',
        'time_slot',
        'subject_name',
        'sort_order',
    ];

    protected $casts = [
        'exam_session_id' => 'integer',
        'day_number' => 'integer',
        'sort_order' => 'integer',
    ];

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }
}