<?php
namespace App\Http\Requests\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $userId = $this->route('user');
        return [
            'name'      => ['sometimes', 'string', 'max:150'],
            'email'     => ['sometimes', 'email', 'max:190', "unique:users,email,{$userId}"],
            // letters()+numbers(): mínimo razonable sin volver inusable el alta.
            // La confirmación evita dar de alta a alguien con una errata.
            'password'  => ['sometimes', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'role'      => ['sometimes', 'in:super_admin,admin,user,cashier'],
            'active'    => ['sometimes', 'boolean'],
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
        ];
    }
}
