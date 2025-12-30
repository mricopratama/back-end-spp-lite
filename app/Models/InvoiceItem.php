<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'amount',
        'paid_amount',
        'status',
        'period_month',
        'fee_category_id',
        'student_id',
        'academic_year_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'period_month' => 'integer',
    ];

    /**
     * Get the student that owns this invoice.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the academic year for this invoice.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get all payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeForPeriod($query, $month)
    {
        return $query->where('period_month', $month);
    }

    /**
     * Scope untuk filter invoice SPP bulanan
     */
    public function scopeMonthlySpp($query)
    {
        return $query->where('invoice_type', 'spp_monthly');
    }

    /**
     * Get period name in Indonesian
     */
    public function getPeriodNameAttribute()
    {
        if (!$this->period_month) {
            return null;
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$this->period_month] . ' ';
    }

    /**
     * Get the invoice that owns this invoice item.
     */
    // public function invoice(): BelongsTo
    // {
    //     return $this->belongsTo(Invoice::class);
    // }

    /**
     * Get the fee category for this invoice item.
     */
    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }
}
