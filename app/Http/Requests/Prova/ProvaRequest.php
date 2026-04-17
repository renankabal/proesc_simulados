<?php

namespace App\Http\Requests\Prova;

use App\Domain\Prova\Enums\StatusProva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProvaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization via Policy in controller
    }

    public function rules(): array
    {
        return [
            'titulo'          => ['required', 'string', 'max:255'],
            'disciplina'      => ['nullable', 'string', 'max:100'],
            'turma'           => ['nullable', 'string', 'max:100'],
            'total_questoes'  => ['required', 'integer', 'min:1', 'max:200'],
            'nota_maxima'     => ['required', 'numeric', 'min:0', 'max:9999'],
            'data_aplicacao'  => ['nullable', 'date'],
            'status'          => ['nullable', Rule::enum(StatusProva::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo'         => 'título',
            'total_questoes' => 'total de questões',
            'nota_maxima'    => 'nota máxima',
            'data_aplicacao' => 'data de aplicação',
        ];
    }
}
