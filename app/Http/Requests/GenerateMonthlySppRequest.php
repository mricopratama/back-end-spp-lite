<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400)
        );
    }
}
