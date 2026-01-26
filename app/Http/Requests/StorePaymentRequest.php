<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePaymentRequest extends FormRequest
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
            'invoice_item_id' => 'required|exists:invoice_items,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:CASH,TRANSFER',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'invoice_item_id.required' => 'Invoice Item ID wajib diisi',
            'invoice_item_id.exists' => 'Invoice Item tidak ditemukan',
            'amount.required' => 'Jumlah pembayaran wajib diisi',
            'amount.numeric' => 'Jumlah pembayaran harus berupa angka',
            'amount.min' => 'Jumlah pembayaran minimal 0',
            'payment_method.required' => 'Metode pembayaran wajib diisi',
            'payment_method.in' => 'Metode pembayaran harus CASH atau TRANSFER',
            'notes.max' => 'Catatan maksimal 500 karakter',
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
