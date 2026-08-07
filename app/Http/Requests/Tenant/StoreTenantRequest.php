<?php
namespace App\Http\Requests\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isSuperAdmin(); }
    public function rules(): array {
        return [
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email', 'unique:tenants,email'],
            'slug'           => ['nullable', 'string', 'max:100', 'unique:tenants,slug', 'regex:/^[a-z0-9\-]+$/'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'plan'           => ['nullable', 'in:free,basic,pro'],
            'admin_name'     => ['required', 'string', 'max:150'],
            'admin_email'    => ['required', 'email', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ];
    }
    protected function prepareForValidation(): void {
        if (!$this->slug && $this->name) {
            $this->merge(['slug' => Str::slug($this->name)]);
        }
    }
}
