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
}
