<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:teams,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do time é obrigatório.',
            'name.unique' => 'Já existe um time com esse nome.',
            'name.max' => 'O nome do time não pode ter mais de 255 caracteres.',
        ];
    }
}
