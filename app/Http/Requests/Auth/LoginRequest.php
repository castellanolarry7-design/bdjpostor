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
            'email'       => ['required', 'string', 'email', 'max:190'],
            // Sin mínimo aquí: la política de longitud va al crear la cuenta.
            // El máximo evita que alguien mande cadenas enormes solo para
            // hacer trabajar a bcrypt (denegación de servicio barata).
            'password'    => ['required', 'string', 'max:200'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'El email es requerido.',
            'email.email'       => 'El formato del email no es válido.',
            'password.required' => 'La contraseña es requerida.',
        ];
    }
}
