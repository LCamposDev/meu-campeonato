<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChampionshipRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_ids' => 'required|array|size:8',
            'team_ids.*' => 'required|integer|exists:teams,id|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'team_ids.required' => 'Os times são obrigatórios.',
            'team_ids.size' => 'O campeonato deve ter exatamente 8 times.',
            'team_ids.*exists' => 'Um ou mais times não foram encontrados.',
            'team_ids.*distinct' => 'Não é permitido repetir times no campeonato.',
        ];
    }
}
