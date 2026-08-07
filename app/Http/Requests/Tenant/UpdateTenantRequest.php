<?php
namespace App\Http\Requests\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isSuperAdmin(); }
    public function rules(): array {
        $tenantId = $this->route('tenant');
        return [
            'name'   => ['sometimes', 'string', 'max:150'],
            'email'  => ['sometimes', 'email', "unique:tenants,email,{$tenantId}"],
            'phone'  => ['nullable', 'string', 'max:30'],
            'plan'   => ['sometimes', 'in:free,basic,pro'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
