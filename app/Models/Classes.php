<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classes extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
    ];

    /**
     * Get all student class history records for this class.
     */
    public function studentClassHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class, 'class_id');
    }

    /**
     * Get all students currently in this class (through the most recent academic year).
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_class_history', 'class_id', 'student_id')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }

     /**
     * Get all academic years associated with this class through student class histories.
     */
    public function academicYears()
    {
        return $this->hasManyThrough(
            \App\Models\AcademicYear::class,
            \App\Models\StudentClassHistory::class,
            'class_id', // Foreign key on StudentClassHistory
            'id',       // Foreign key on AcademicYear
            'id',       // Local key on Classes
            'academic_year_id' // Local key on StudentClassHistory
        )->distinct();
    }
}
