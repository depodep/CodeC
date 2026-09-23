<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_period_id',
        'grade_level',
        'section_name',
    ];

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function students()
    {
        return $this->belongsToMany(
            User::class,
            'section_student',
            'section_id',
            'student_id'
        );
    }
}