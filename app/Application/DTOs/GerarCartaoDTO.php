<?php

namespace App\Application\DTOs;

readonly class GerarCartaoDTO
{
    public function __construct(
        public string  $provaId,
        public string  $codigoAluno,
        public ?string $nomeAluno = null,
        public ?string $turma = null,
        public int     $tentativa = 1,
        public ?string $geradoPor = null,
    ) {}

    public static function fromArray(array $data, ?string $geradoPor = null): self
    {
        return new self(
            provaId:     $data['prova_id'],
            codigoAluno: $data['codigo_aluno'],
            nomeAluno:   $data['nome_aluno'] ?? null,
            turma:       $data['turma'] ?? null,
            tentativa:   (int) ($data['tentativa'] ?? 1),
            geradoPor:   $geradoPor,
        );
    }
}
