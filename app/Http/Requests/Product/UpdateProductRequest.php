<?php
namespace App\Http\Requests\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $productId = $this->route('product');
        return [
            'name'          => ['sometimes', 'string', 'max:200'],
            'sku'           => ['sometimes', 'string', 'max:100'],
            'barcode'       => ['nullable', 'string', 'max:100'],
            'category'      => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'stock_minimum' => ['sometimes', 'integer', 'min:0'],
            'unit'          => ['sometimes', 'string', 'max:30'],
            'cost'          => ['sometimes', 'numeric', 'min:0'],
            'price'         => ['sometimes', 'numeric', 'min:0'],
            'supplier'      => ['nullable', 'string', 'max:200'],
            'active'        => ['sometimes', 'boolean'],
        ];
    }
}
