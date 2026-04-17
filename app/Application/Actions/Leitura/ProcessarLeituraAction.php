<?php

namespace App\Application\Actions\Leitura;

use App\Application\DTOs\ProcessarLeituraDTO;
use App\Application\DTOs\RespostaOMRDTO;
use App\Domain\Cartao\Models\CartaoResposta;
use App\Domain\Leitura\Enums\StatusLeitura;
use App\Domain\Leitura\Models\Leitura;
use App\Domain\Leitura\Models\RespostaAluno;
use App\Support\Traits\HasAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessarLeituraAction
{
    use HasAuditLog;

    public function execute(ProcessarLeituraDTO $dto, array $respostasOMR): Leitura
    {
        return DB::transaction(function () use ($dto, $respostasOMR) {
            $cartao = CartaoResposta::findOrFail($dto->cartaoId);

            // Salva imagem no disco
            $imagemPath = $this->salvarImagem($dto->imagemBase64, $dto->cartaoId);

            $leitura = Leitura::create([
                'cartao_id'        => $cartao->id,
                'lido_por'         => $dto->lidoPor,
                'status'           => StatusLeitura::Pendente,
                'imagem_path'      => $imagemPath,
                'metadados_omr'    => $dto->metadadosOmr,
                'origem'           => $dto->origem,
            ]);

            // Salva respostas detectadas pelo OMR
            foreach ($respostasOMR as $respostaData) {
                $resposta = RespostaOMRDTO::fromArray($respostaData);

                RespostaAluno::create([
                    'leitura_id'      => $leitura->id,
                    'questao_numero'  => $resposta->questaoNumero,
                    'marcacao'        => $resposta->marcacao,
                    'dupla_marcacao'  => $resposta->duplaMarcacao,
                    'em_branco'       => $resposta->emBranco,
                    'confianca'       => $resposta->confianca,
                ]);
            }

            static::logInfo('leitura.processada', 'leituras', $leitura->id, [
                'cartao_id'       => $cartao->id,
                'total_respostas' => count($respostasOMR),
            ]);

            return $leitura;
        });
    }

    private function salvarImagem(string $base64, string $cartaoId): string
    {
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = base64_decode($imageData);

        $path = "leituras/{$cartaoId}/" . now()->format('YmdHis') . '.jpg';
        Storage::disk('local')->put($path, $imageData);

        return $path;
    }
}
