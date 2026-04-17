<?php

namespace App\Application\DTOs;

use App\Domain\Prova\Enums\StatusProva;

readonly class CriarProvaDTO
{
    public function __construct(
        public string      $userId,
        public string      $titulo,
        public ?string     $disciplina,
        public ?string     $turma,
        public int         $totalQuestoes,
        public float       $notaMaxima,
        public ?string     $dataAplicacao = null,
        public StatusProva $status = StatusProva::Rascunho,
    ) {}

    public static function fromArray(array $data, string $userId): self
    {
        return new self(
            userId:        $userId,
            titulo:        $data['titulo'],
            disciplina:    $data['disciplina'] ?? null,
            turma:         $data['turma'] ?? null,
            totalQuestoes: (int) $data['total_questoes'],
            notaMaxima:    (float) ($data['nota_maxima'] ?? 10),
            dataAplicacao: $data['data_aplicacao'] ?? null,
            status:        StatusProva::from($data['status'] ?? StatusProva::Rascunho->value),
        );
    }
}
