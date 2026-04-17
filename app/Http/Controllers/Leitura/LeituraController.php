<?php

namespace App\Http\Controllers\Leitura;

use App\Domain\Prova\Models\Prova;
use App\Domain\Resultado\Models\Resultado;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LeituraController extends Controller
{
    public function index(Request $request): View
    {
        return view('leitura.webcam');
    }

    public function resultados(Request $request): View
    {
        $query  = $this->buildResultadosQuery($request);
        $provas = $this->provasDoUsuario($request);

        $resultados = $query->paginate(25)->withQueryString();

        return view('resultados.index', compact('resultados', 'provas'));
    }

    public function exportCsv(Request $request): Response
    {
        $query      = $this->buildResultadosQuery($request);
        $resultados = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="resultados_' . now()->format('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($resultados) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));  // UTF-8 BOM

            fputcsv($out, ['Código Aluno', 'Nome Aluno', 'Turma', 'Prova', 'Acertos', 'Erros', 'Brancos', 'Anuladas', 'Total', 'Nota Final', '% Acerto', 'Calculado Em'], ';');

            foreach ($resultados as $r) {
                fputcsv($out, [
                    $r->leitura->cartao->codigo_aluno,
                    $r->leitura->cartao->nome_aluno ?? '',
                    $r->leitura->cartao->turma ?? '',
                    $r->leitura->cartao->prova->titulo,
                    $r->total_acertos,
                    $r->total_erros,
                    $r->total_brancos,
                    $r->total_anuladas,
                    $r->total_questoes,
                    number_format($r->nota_final, 2, ',', ''),
                    number_format($r->percentual_acerto, 2, ',', '') . '%',
                    $r->calculado_em?->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildResultadosQuery(Request $request)
    {
        $query = Resultado::query()
            ->with(['leitura.cartao.prova', 'gabarito'])
            ->orderByDesc('calculado_em');

        if (!$request->user()->isAdmin()) {
            $query->whereHas('leitura.cartao.prova', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        if ($provaId = $request->get('prova_id')) {
            $query->whereHas('leitura.cartao', fn ($q) => $q->where('prova_id', $provaId));
        }

        if ($search = $request->get('q')) {
            $query->whereHas('leitura.cartao', fn ($q) => $q->where('codigo_aluno', 'ilike', "%{$search}%")
                ->orWhere('nome_aluno', 'ilike', "%{$search}%"));
        }

        return $query;
    }

    private function provasDoUsuario(Request $request)
    {
        $query = Prova::orderBy('titulo');
        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }
        return $query->pluck('titulo', 'id');
    }
}
