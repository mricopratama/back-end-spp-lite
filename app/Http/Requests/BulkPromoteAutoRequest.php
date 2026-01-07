<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkPromoteAutoRequest extends FormRequest
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
            'from_academic_year_id' => 'required|exists:academic_years,id',
            'to_academic_year_id' => 'required|exists:academic_years,id|different:from_academic_year_id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'from_academic_year_id.required' => 'Tahun ajaran asal wajib diisi',
            'from_academic_year_id.exists' => 'Tahun ajaran asal tidak valid',
            'to_academic_year_id.required' => 'Tahun ajaran tujuan wajib diisi',
            'to_academic_year_id.exists' => 'Tahun ajaran tujuan tidak valid',
            'to_academic_year_id.different' => 'Tahun ajaran tujuan harus berbeda dengan tahun ajaran asal',
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
