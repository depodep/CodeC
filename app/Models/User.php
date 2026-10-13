<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array and JSON forms.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'track_name',
        'section_name',
        'gender_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Tinanggal na ang 'password' => 'hashed' para sa plain text storage
            'is_active'         => 'boolean',
            'is_principal'      => 'boolean',
            'grade_level'       => 'string',
            'strand'            => 'string',
            'section'           => 'string',
        ];
    }

    /* ================= RELATIONSHIPS ================= */

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function nfcCard()
    {
        return $this->hasOne(NfcCard::class, 'user_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'user_id');
    }

    public function sections()
    {
        return $this->belongsToMany(AcademicSection::class, 'section_student', 'student_id', 'section_id');
    }

    public function classSchedules()
    {
        return $this->hasMany(ClassSchedule::class, 'teacher_id');
    }

    /* ================= ACCESSORS & MUTATORS ================= */

    /**
     * Resolve strand/track directly from admin input.
     */
    public function getTrackNameAttribute(): ?string
    {
        return $this->strand ?? null;
    }

    /**
     * Resolve section directly from admin input.
     */
    public function getSectionNameAttribute(): ?string
    {
        return $this->section ?? null;
    }

    /**
     * Resolve gender into readable string.
     * Supports both string ('Male', 'Female') and numeric (1, 2) values.
     */
    public function getGenderNameAttribute(): string
    {
        $g = strtolower(trim((string)($this->gender ?? '')));
        if ($g === 'male' || $g === 'm' || $g === '1') {
            return 'Male';
        }
        if ($g === 'female' || $g === 'f' || $g === '2') {
            return 'Female';
        }
        return 'Not Specified';
    }

    /**
     * Get formatted full name.
     */
    public function getFullNameAttribute(): string
    {
        $middle = !empty($this->middle_name) ? ' ' . strtoupper(substr($this->middle_name, 0, 1)) . '.' : '';
        return trim("{$this->last_name}, {$this->first_name}{$middle}");
    }

    /**
     * Get contact person contact (parent phone number or student phone number).
     */
    public function getContactPersonContactAttribute(): ?string
    {
        return $this->parent_phone_number ?: $this->phone_number;
    }

    /**
     * Primary contact number for direct messaging.
     */
    public function getContactNumberAttribute(): ?string
    {
        return $this->phone_number ?: $this->parent_phone_number;
    }
}