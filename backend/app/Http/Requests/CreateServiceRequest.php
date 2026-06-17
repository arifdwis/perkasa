<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'service_category_id' => ['required', 'uuid', 'exists:service_categories,id'],
            'description' => ['required', 'string'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'lokasi_layanan' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }
}
