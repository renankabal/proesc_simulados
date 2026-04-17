<?php

namespace App\Application\DTOs;

readonly class RespostaOMRDTO
{
    public function __construct(
        public int     $questaoNumero,
        public ?string $marcacao,
        public bool    $duplaMarcacao = false,
        public bool    $emBranco = false,
        public ?float  $confianca = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            questaoNumero: (int) $data['questao_numero'],
            marcacao:      $data['marcacao'] ?? null,
            duplaMarcacao: (bool) ($data['dupla_marcacao'] ?? false),
            emBranco:      (bool) ($data['em_branco'] ?? false),
            confianca:     isset($data['confianca']) ? (float) $data['confianca'] : null,
        );
    }

    public function isValida(): bool
    {
        return !$this->duplaMarcacao && !$this->emBranco && $this->marcacao !== null;
    }
}
