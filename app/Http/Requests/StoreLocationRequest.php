<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'timezone' => ['required', 'timezone'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome ao local.',
            'name.max' => 'O nome pode ter no máximo 255 caracteres.',
            'latitude.required' => 'Informe a latitude.',
            'latitude.numeric' => 'A latitude precisa ser um número.',
            'latitude.between' => 'A latitude fica entre -90 e 90.',
            'longitude.required' => 'Informe a longitude.',
            'longitude.numeric' => 'A longitude precisa ser um número.',
            'longitude.between' => 'A longitude fica entre -180 e 180.',
            'timezone.required' => 'Escolha o fuso horário.',
            'timezone.timezone' => 'Escolha um fuso horário da lista.',
        ];
    }
}
