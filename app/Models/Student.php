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
