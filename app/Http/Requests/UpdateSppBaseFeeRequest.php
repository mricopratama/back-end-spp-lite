<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSppBaseFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'spp_base_fee' => 'required|numeric|min:0|max:99999999',
        ];
    }

    public function messages(): array
    {
        return [
            'spp_base_fee.required' => 'SPP base fee harus diisi',
            'spp_base_fee.numeric' => 'SPP base fee harus berupa angka',
            'spp_base_fee.min' => 'SPP base fee minimal 0',
            'spp_base_fee.max' => 'SPP base fee terlalu besar',
        ];
    }
}
