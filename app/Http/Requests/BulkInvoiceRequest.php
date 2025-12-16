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
            'academic_year_id.required' => 'Tahun ajaran wajib diisi',
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi',
            'items.required' => 'Items invoice wajib diisi',
            'items.min' => 'Minimal 1 item invoice',
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
