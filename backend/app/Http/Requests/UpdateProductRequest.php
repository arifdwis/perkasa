<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'product_category_id' => ['required', 'uuid', 'exists:product_categories,id'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive,out_of_stock'],
            'is_featured' => ['nullable', 'boolean'],
            'product_type' => ['nullable', 'string', 'in:regular,pre_order'],
            'pre_order_deadline' => ['required_if:product_type,pre_order', 'date'],
            'pre_order_estimated_ship' => ['required_if:product_type,pre_order', 'date', 'after_or_equal:pre_order_deadline'],
            'pre_order_min_qty' => ['nullable', 'integer', 'min:1'],
            'pre_order_max_qty' => ['nullable', 'integer', 'gte:pre_order_min_qty'],
        ];
    }

    public function messages(): array
    {
        return [
            'pre_order_deadline.required_if' => 'Batas waktu pre-order wajib diisi.',
            'pre_order_estimated_ship.required_if' => 'Estimasi pengiriman wajib diisi.',
            'pre_order_estimated_ship.after_or_equal' => 'Estimasi pengiriman harus setelah atau sama dengan batas waktu.',
            'pre_order_max_qty.gte' => 'Maksimal qty pre-order harus ≥ minimal qty.',
        ];
    }
}
