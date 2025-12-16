<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkPromoteRequest extends FormRequest
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
            'to_academic_year_id' => 'required|exists:academic_years,id',
            'class_mapping' => 'required|array|min:1',
            'class_mapping.*.from_class_id' => 'required|exists:classes,id',
            'class_mapping.*.to_class_id' => 'required|exists:classes,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'from_academic_year_id.required' => 'Tahun ajaran asal wajib diisi',
            'to_academic_year_id.required' => 'Tahun ajaran tujuan wajib diisi',
            'class_mapping.required' => 'Mapping kelas wajib diisi',
            'class_mapping.*.from_class_id.required' => 'Kelas asal wajib diisi',
            'class_mapping.*.to_class_id.required' => 'Kelas tujuan wajib diisi',
        ];
    }
}
