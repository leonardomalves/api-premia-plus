<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'O nome deve ser um texto válido',
            'name.max' => 'O nome não pode ter mais de 255 caracteres',
            'phone.string' => 'O telefone deve ser um texto válido',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres',
            'email.email' => 'Informe um email válido',
            'email.unique' => 'Este email já está sendo usado',
            'username.unique' => 'Este nome de usuário já está sendo usado',
        ];
    }
}