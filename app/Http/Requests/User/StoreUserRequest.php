<?php
namespace App\Http\Requests\User;
use Illuminate\Foundation\Http\FormRequest;

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
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'role'      => ['required', 'in:super_admin,admin,user,cashier'],
            'tenant_id' => [$isSuperAdmin ? 'nullable' : 'prohibited', 'exists:tenants,id'],
        ];
    }
}
