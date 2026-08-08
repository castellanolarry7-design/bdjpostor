<?php
namespace App\Http\Requests\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool {
        return in_array($this->user()->role, ['super_admin', 'admin']);
    }
    public function rules(): array {
        $isSuperAdmin = $this->user()->isSuperAdmin();
        return [
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'unique:users,email'],
            // letters()+numbers(): mínimo razonable sin volver inusable el alta.
            // La confirmación evita dar de alta a alguien con una errata.
            'password'  => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'role'      => ['required', 'in:super_admin,admin,user,cashier'],
            'tenant_id' => [$isSuperAdmin ? 'nullable' : 'prohibited', 'exists:tenants,id'],
        ];
    }
}
