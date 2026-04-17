<?php

namespace App\Application\DTOs;

readonly class ProcessarLeituraDTO
{
    public function __construct(
        public string  $cartaoId,
        public string  $imagemBase64,
        public string  $origem = 'webcam',
        public ?string $lidoPor = null,
        public array   $metadadosOmr = [],
    ) {}

    public static function fromRequest(array $data, ?string $userId): self
    {
        return new self(
            cartaoId:     $data['cartao_id'],
            imagemBase64: $data['imagem'],
            origem:       $data['origem'] ?? 'webcam',
            lidoPor:      $userId,
            metadadosOmr: $data['metadados'] ?? [],
        );
    }
}
