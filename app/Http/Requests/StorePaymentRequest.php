<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'payment_date' => 'required|date',
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
            'payment_date.required' => 'Tanggal pembayaran wajib diisi',
            'payment_date.date' => 'Format tanggal pembayaran tidak valid',
            'payment_method.required' => 'Metode pembayaran wajib diisi',
            'payment_method.in' => 'Metode pembayaran harus CASH atau TRANSFER',
            'notes.max' => 'Catatan maksimal 500 karakter',
        ];
    }
}
