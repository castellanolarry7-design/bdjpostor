<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cualquiera puede intentar hacer login
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'string', 'email'],
            'password'    => ['required', 'string', 'min:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'El email es requerido.',
            'email.email'       => 'El formato del email no es válido.',
            'password.required' => 'La contraseña es requerida.',
            'password.min'      => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }
}
