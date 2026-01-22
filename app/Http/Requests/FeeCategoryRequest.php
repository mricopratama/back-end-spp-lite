<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FeeCategoryRequest extends FormRequest
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
        $feeCategory = $this->route('fee_category');

        $nameRules = [
            'required',
            'string',
            'max:100',
        ];

        // Add unique rule with proper handling for both create and update
        if ($feeCategory) {
            // Update: ignore current record
            $nameRules[] = 'unique:fee_categories,name,' . $feeCategory . ',id';
        } else {
            // Create: simple unique check
            $nameRules[] = 'unique:fee_categories,name';
        }

        return [
            'name' => $nameRules,
            'type' => 'required|in:spp,ekstrakurikuler,buku,pendaftaran,lainnya',
            'default_amount' => 'required|integer|min:0',
            'description' => 'nullable|string|max:255'
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
