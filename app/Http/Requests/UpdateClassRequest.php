<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateClassRequest extends FormRequest
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
        // Untuk update: hanya terima level (name tidak bisa diubah)
        return [
            'name' => 'prohibited', // Tolak jika ada field name
            'level' => 'required|integer|min:1|max:6'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.prohibited' => 'Field name tidak boleh diisi. Nama kelas akan di-generate otomatis berdasarkan level.',
            'level.required' => 'Field level wajib diisi',
            'level.integer' => 'Field level harus berupa angka',
            'level.min' => 'Level minimal adalah 1',
            'level.max' => 'Level maksimal adalah 6',
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
