<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class CaptureLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'preferences' => ['nullable', 'array'],
            'preferences.*' => ['string', 'max:50'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'utm_term' => ['nullable', 'string', 'max:100'],
            'utm_content' => ['nullable', 'string', 'max:100'],
            'referrer' => ['nullable', 'url', 'max:255'],
            'landing_page' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('O nome é obrigatório.'),
            'name.min' => __('O nome deve ter pelo menos :min caracteres.'),
            'name.max' => __('O nome não pode ter mais de :max caracteres.'),
            'email.required' => __('O e-mail é obrigatório.'),
            'email.email' => __('Digite um e-mail válido.'),
            'email.max' => __('O e-mail não pode ter mais de :max caracteres.'),
            'phone.min' => __('O telefone deve ter pelo menos :min dígitos.'),
            'phone.max' => __('O telefone não pode ter mais de :max caracteres.'),
            'preferences.array' => __('As preferências devem ser uma lista válida.'),
            'preferences.*.string' => __('Cada preferência deve ser um texto válido.'),
            'preferences.*.max' => __('Cada preferência não pode ter mais de :max caracteres.'),
            'utm_source.max' => __('UTM Source não pode ter mais de :max caracteres.'),
            'utm_medium.max' => __('UTM Medium não pode ter mais de :max caracteres.'),
            'utm_campaign.max' => __('UTM Campaign não pode ter mais de :max caracteres.'),
            'utm_term.max' => __('UTM Term não pode ter mais de :max caracteres.'),
            'utm_content.max' => __('UTM Content não pode ter mais de :max caracteres.'),
            'referrer.url' => __('O referrer deve ser uma URL válida.'),
            'referrer.max' => __('O referrer não pode ter mais de :max caracteres.'),
            'landing_page.url' => __('A landing page deve ser uma URL válida.'),
            'landing_page.max' => __('A landing page não pode ter mais de :max caracteres.'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('nome'),
            'email' => __('e-mail'),
            'phone' => __('telefone'),
            'preferences' => __('preferências'),
            'utm_source' => __('origem da campanha'),
            'utm_medium' => __('meio da campanha'),
            'utm_campaign' => __('campanha'),
            'utm_term' => __('termo da campanha'),
            'utm_content' => __('conteúdo da campanha'),
            'referrer' => __('referrer'),
            'landing_page' => __('página de origem'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize phone number
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^0-9+]/', '', (string) $this->input('phone')),
            ]);
        }

        // Convert preferences to array if it's a string
        if ($this->has('preferences') && is_string($this->input('preferences'))) {
            $preferences = explode(',', (string) $this->input('preferences'));
            $this->merge([
                'preferences' => array_map('trim', $preferences),
            ]);
        }
    }
}
