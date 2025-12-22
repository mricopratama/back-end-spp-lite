<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'invoice_type' => 'required|in:spp_monthly,spp_yearly,other_fee,other',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2024|max:2030',
            'due_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.fee_category_id' => 'required|exists:fee_categories,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.custom_amount' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Student ID wajib diisi',
            'student_id.exists' => 'Siswa tidak ditemukan',
            'academic_year_id.required' => 'Tahun ajaran wajib diisi',
            'invoice_type.required' => 'Tipe invoice wajib diisi',
            'invoice_type.in' => 'Tipe invoice tidak valid',
            'period_month.required' => 'Bulan periode wajib diisi',
            'period_month.min' => 'Bulan periode minimal 1 (Januari)',
            'period_month.max' => 'Bulan periode maksimal 12 (Desember)',
            'period_year.required' => 'Tahun periode wajib diisi',
            'period_year.min' => 'Tahun periode minimal 2024',
            'period_year.max' => 'Tahun periode maksimal 2030',
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi',
            'items.required' => 'Items invoice wajib diisi',
            'items.min' => 'Minimal 1 item invoice',
            'items.*.fee_category_id.required' => 'Kategori biaya wajib diisi',
            'items.*.description.required' => 'Deskripsi item wajib diisi',
        ];
    }
}
