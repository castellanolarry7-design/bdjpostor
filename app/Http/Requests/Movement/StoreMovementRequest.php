<?php
namespace App\Http\Requests\Movement;
use Illuminate\Foundation\Http\FormRequest;

class StoreMovementRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'product_id' => ['required', 'string', 'exists:products,id'],
            'type'       => ['required', 'in:entrada,salida,ajuste'],
            'quantity'   => ['required', 'integer', 'min:1'],
            'unit_cost'  => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'note'       => ['nullable', 'string', 'max:500'],
            'reference'  => ['nullable', 'string', 'max:100'],
            'moved_at'   => ['nullable', 'date'],
        ];
    }
}
