<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'sponsor' => 'nullable|string|exists:users,username',
            'username' => 'required|string|max:255|unique:users',
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
            'name.required' => __('validation.required', ['attribute' => 'nome']),
            'email.required' => __('validation.required', ['attribute' => 'email']),
            'email.email' => __('validation.email', ['attribute' => 'email']),
            'email.unique' => __('validation.unique', ['attribute' => 'email']),
            'password.required' => __('validation.required', ['attribute' => 'senha']),
            'password.min' => __('validation.min.string', ['attribute' => 'senha', 'min' => 8]),
            'password.confirmed' => __('validation.confirmed', ['attribute' => 'senha']),
            'username.required' => __('validation.required', ['attribute' => 'nome de usuário']),
            'username.unique' => __('validation.unique', ['attribute' => 'nome de usuário']),
            'sponsor.exists' => __('validation.exists', ['attribute' => 'patrocinador']),
        ];
    }
}