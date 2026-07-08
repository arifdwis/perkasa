<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'product_category_id' => ['required', 'uuid', 'exists:product_categories,id'],
            'description' => ['required', 'string'],
            'price' => ['required_if:variants,null,0', 'numeric', 'min:0'],
            'stock' => ['required_if:variants,null,0', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive,out_of_stock'],
            'is_featured' => ['nullable', 'boolean'],
            'product_type' => ['nullable', 'string', 'in:regular,pre_order'],
            'pre_order_deadline' => ['required_if:product_type,pre_order', 'date', 'after:now'],
            'pre_order_estimated_ship' => ['required_if:product_type,pre_order', 'date', 'after_or_equal:pre_order_deadline'],
            'pre_order_min_qty' => ['nullable', 'integer', 'min:1'],
            'pre_order_max_qty' => ['nullable', 'integer', 'gte:pre_order_min_qty'],
            'is_flash_sale' => ['nullable', 'boolean'],
            'flash_sale_price' => ['required_if:is_flash_sale,true', 'nullable', 'numeric', 'min:0'],
            'flash_sale_start' => ['required_if:is_flash_sale,true', 'nullable', 'date'],
            'flash_sale_end' => ['required_if:is_flash_sale,true', 'nullable', 'date', 'after:flash_sale_start'],
            'variants' => ['nullable', 'array', 'max:10'],
            'variants.*.name' => ['required', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'pre_order_deadline.required_if' => 'Batas waktu pre-order wajib diisi.',
            'pre_order_deadline.after' => 'Batas waktu pre-order harus setelah waktu sekarang.',
            'pre_order_estimated_ship.required_if' => 'Estimasi pengiriman wajib diisi.',
            'pre_order_estimated_ship.after_or_equal' => 'Estimasi pengiriman harus setelah atau sama dengan batas waktu.',
            'pre_order_max_qty.gte' => 'Maksimal qty pre-order harus ≥ minimal qty.',
            'flash_sale_price.required_if' => 'Harga flash sale wajib diisi.',
            'flash_sale_start.required_if' => 'Tanggal mulai flash sale wajib diisi.',
            'flash_sale_end.required_if' => 'Tanggal berakhir flash sale wajib diisi.',
            'flash_sale_end.after' => 'Tanggal berakhir harus setelah tanggal mulai.',
        ];
    }
}
