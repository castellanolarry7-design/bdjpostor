<?php
namespace App\Http\Requests\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $tenantId = $this->user()->tenant_id ?? $this->tenant_id;
        return [
            'name'          => ['required', 'string', 'max:200'],
            'sku'           => ['required', 'string', 'max:100'],
            'barcode'       => ['nullable', 'string', 'max:100'],
            'category'      => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'stock_initial' => ['nullable', 'integer', 'min:0'],
            'stock_minimum' => ['nullable', 'integer', 'min:0'],
            'unit'          => ['nullable', 'string', 'max:30'],
            'cost'          => ['nullable', 'numeric', 'min:0'],
            'price'         => ['nullable', 'numeric', 'min:0'],
            'supplier'      => ['nullable', 'string', 'max:200'],
            'tenant_id'     => [$this->user()->isSuperAdmin() ? 'required' : 'prohibited', 'exists:tenants,id'],
        ];
    }
}
