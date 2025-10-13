<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request responsável por validar pedidos de atualização de perfil.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determina se o utilizador está autorizado a fazer este pedido.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Regras de validação aplicáveis ao pedido.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Exemplo opcional:
            // 'avatar' => ['nullable', 'url'],
        ];
    }

    /**
     * Mensagens de erro customizadas (opcional).
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este email já está em uso por outro utilizador.',
        ];
    }
}
