<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMonthlySppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required_without:student_ids|exists:classes,id',
            'student_ids' => 'required_without:class_id|array',
            'student_ids.*' => 'exists:students,id',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2024|max:2030',
            'due_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'Tahun ajaran harus dipilih',
            'academic_year_id.exists' => 'Tahun ajaran tidak valid',
            'period_month.required' => 'Bulan periode harus diisi',
            'period_month.min' => 'Bulan harus antara 1-12',
            'period_month.max' => 'Bulan harus antara 1-12',
            'period_year.required' => 'Tahun periode harus diisi',
            'due_date.required' => 'Tanggal jatuh tempo harus diisi',
            'due_date.date' => 'Format tanggal tidak valid',
        ];
    }
}
