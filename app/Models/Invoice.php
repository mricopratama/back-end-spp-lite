<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'title',
        'period_month',
        'period_year',
        'invoice_type',
        'total_amount',
        'paid_amount',
        'status',
        'due_date',
        'student_id',
        'academic_year_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'period_month' => 'integer',
        'period_year' => 'integer',
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
     * Get all invoice items for this invoice.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Alias for invoiceItems
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get all payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope untuk filter invoice SPP bulanan
     */
    public function scopeMonthlySpp($query)
    {
        return $query->where('invoice_type', 'spp_monthly');
    }

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopeForPeriod($query, $month, $year)
    {
        return $query->where('period_month', $month)
                     ->where('period_year', $year);
    }

    /**
     * Get period name in Indonesian
     */
    public function getPeriodNameAttribute()
    {
        if (!$this->period_month || !$this->period_year) {
            return null;
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$this->period_month] . ' ' . $this->period_year;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue()
    {
        return $this->status !== 'paid' && $this->due_date < now();
    }

    /**
     * Get overdue days
     */
    public function getOverdueDaysAttribute()
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return now()->diffInDays($this->due_date);
    }
}
