<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkInvoiceRequest extends FormRequest
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
            'class_id' => 'nullable|exists:classes,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'fee_category_id' => 'required|exists:fee_categories,id',
            'amount' => 'nullable|numeric|min:0',
            'period_month' => 'required|integer|min:1|max:12',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'Tahun ajaran wajib diisi',
            'fee_category_id.required' => 'Kategori biaya wajib diisi',
            'fee_category_id.exists' => 'Kategori biaya tidak ditemukan',
            'amount.numeric' => 'Nominal harus berupa angka',
            'amount.min' => 'Nominal minimal 0',
            'period_month.required' => 'Bulan periode wajib diisi',
            'period_month.min' => 'Bulan periode minimal 1 (Januari)',
            'period_month.max' => 'Bulan periode maksimal 12 (Desember)',
        ];
    }

    /**
     * Additional validation logic
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Either class_id or student_ids must be provided
            if (!$this->class_id && !$this->student_ids) {
                $validator->errors()->add(
                    'class_id',
                    'Harus mengisi class_id atau student_ids'
                );
            }
        });
    }
}
