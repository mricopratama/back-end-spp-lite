<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
            'nis' => 'required|string|max:50|unique:students,nis',
            'full_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|in:ACTIVE,INACTIVE,GRADUATED',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi',
            'nis.unique' => 'NIS sudah terdaftar',
            'full_name.required' => 'Nama lengkap wajib diisi',
            'status.required' => 'Status wajib diisi',
            'status.in' => 'Status harus ACTIVE, INACTIVE, atau GRADUATED',
        ];
    }
}
