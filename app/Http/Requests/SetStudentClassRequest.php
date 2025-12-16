<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetStudentClassRequest extends FormRequest
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
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
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
            'class_id.required' => 'Class ID wajib diisi',
            'class_id.exists' => 'Kelas tidak ditemukan',
            'academic_year_id.required' => 'Academic Year ID wajib diisi',
            'academic_year_id.exists' => 'Tahun ajaran tidak ditemukan',
        ];
    }
}
