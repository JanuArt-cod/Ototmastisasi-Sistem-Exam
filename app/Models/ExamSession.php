<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamSession extends Model
{
    protected $fillable = [
        'session_name',
        'school_name',
        'school_npsn',
        'school_address',
        'card_title',
        'exam_title',
        'academic_year',
        'term',
        'exam_date',
        'principal_name',
        'principal_title',
        'logo_path',
        'signature_path',
        'card_note',
        'foundation_name',
        'school_accreditation',
        'issue_city',
        'signatory_nip',
        'stamp_path',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function cardSetting(): HasOne
    {
        return $this->hasOne(CardSetting::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class)
            ->orderBy('day_number')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}