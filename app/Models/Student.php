<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'full_name',
        'address',
        'phone_number',
        'status',
    ];

    protected $casts = [];

    /**
     * Appends attributes to model
     */
    protected $appends = ['current_class_info'];

    /**
     * Get the user account associated with this student.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get all class history records for this student.
     */
    public function classHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    /**
     * Alias for classHistories
     */
    public function classHistory(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    /**
     * Get current class (from most recent academic year)
     */
    public function currentClass()
    {
        return $this->belongsTo(StudentClassHistory::class, 'id', 'student_id')
            ->with(['class', 'academicYear'])
            ->latest('academic_year_id')
            ->limit(1);
    }

    /**
     * Get current class history record with class and academic year
     */
    public function currentClassHistory()
    {
        return $this->hasOne(StudentClassHistory::class)
            ->with(['class', 'academicYear'])
            ->latest('academic_year_id');
    }

    /**
     * Get all invoices for this student.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get current class info as accessor
     */
    public function getCurrentClassInfoAttribute()
    {
        $history = $this->classHistories()
            ->with(['class', 'academicYear'])
            ->latest('academic_year_id')
            ->first();

        if (!$history) {
            return null;
        }

        return [
            'class_id' => $history->class_id,
            'class_name' => $history->class->name ?? null,
            'academic_year_id' => $history->academic_year_id,
            'academic_year_name' => $history->academicYear->name ?? null,
        ];
    }

    /**
     * Get all classes this student has been enrolled in (through student_class_history).
     */
    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'student_class_history', 'student_id', 'class_id')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }
}
