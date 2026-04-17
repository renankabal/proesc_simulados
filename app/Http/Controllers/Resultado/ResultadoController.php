<?php

namespace App\Http\Controllers\Resultado;

use App\Domain\Resultado\Models\Resultado;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultadoController extends Controller
{
    public function show(Request $request, Resultado $resultado): View
    {
        $resultado->load([
            'leitura.cartao.prova.questoes',
            'leitura.respostas',
            'gabarito',
        ]);

        $prova = $resultado->leitura->cartao->prova;

        // Garante que o usuário tem acesso
        if (!$request->user()->isAdmin() && $prova->user_id !== $request->user()->id) {
            abort(403);
        }

        $detalhe = $resultado->detalhe_questoes ?? [];

        // Monta tabela enriquecida por questão
        $questoes = [];
        for ($i = 1; $i <= $prova->total_questoes; $i++) {
            $d = $detalhe[$i] ?? null;
            $questoes[$i] = [
                'numero'   => $i,
                'marcacao' => $d['marcacao'] ?? null,
                'gabarito' => $d['gabarito'] ?? null,
                'status'   => $d['status'] ?? 'sem_dado',
                'anulada'  => $prova->questoes->firstWhere('numero', $i)?->anulada ?? false,
            ];
        }

        return view('resultados.show', compact('resultado', 'prova', 'questoes'));
    }
}
