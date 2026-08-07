<?php
namespace App\Http\Requests\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        $userId = $this->route('user');
        return [
            'name'      => ['sometimes', 'string', 'max:150'],
            'email'     => ['sometimes', 'email', "unique:users,email,{$userId}"],
            'password'  => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role'      => ['sometimes', 'in:super_admin,admin,user,cashier'],
            'active'    => ['sometimes', 'boolean'],
            'tenant_id' => ['sometimes', 'exists:tenants,id'],
        ];
    }
}
