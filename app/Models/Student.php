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
        'spp_base_fee',
    ];

    protected $casts = [
        'spp_base_fee' => 'decimal:2',
    ];

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
        return $this->hasOneThrough(
            Classes::class,
            StudentClassHistory::class,
            'student_id',
            'id',
            'id',
            'class_id'
        )->latest('student_class_history.academic_year_id');
    }

    /**
     * Get all invoices for this student.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
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
