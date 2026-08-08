<?php
namespace App\Http\Requests\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function messages(): array {
        return [
            'sku.unique' => 'Ya existe un producto con ese SKU.',
        ];
    }

    public function rules(): array {
        $tenantId = $this->user()->tenant_id ?? $this->tenant_id;
        return [
            'name'          => ['required', 'string', 'max:200'],
            // SKU único dentro del tenant: sin esta regla, un duplicado revienta
            // contra el índice unique_sku_per_tenant y devuelve un 500 en vez de un 422.
            'sku'           => [
                'required', 'string', 'max:100',
                // Sin whereNull('deleted_at') a propósito: el índice
                // unique_sku_per_tenant tampoco excluye los borrados lógicos,
                // así que un SKU de un producto eliminado sigue ocupado.
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
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
