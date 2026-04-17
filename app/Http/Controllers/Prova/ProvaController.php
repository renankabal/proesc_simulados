<?php

namespace App\Http\Controllers\Prova;

use App\Application\Actions\Prova\CriarProvaAction;
use App\Application\DTOs\CriarProvaDTO;
use App\Domain\Prova\Enums\StatusProva;
use App\Domain\Prova\Models\Prova;
use App\Domain\Resultado\Models\Resultado;
use App\Http\Controllers\Controller;
use App\Http\Requests\Prova\ProvaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProvaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Prova::query()->with('gabaritoAtivo');

        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        $provas = $query->orderByDesc('created_at')->paginate(15);

        return view('provas.index', compact('provas'));
    }

    public function create(): View
    {
        $this->authorize('create', Prova::class);
        return view('provas.create');
    }

    public function store(ProvaRequest $request, CriarProvaAction $action): RedirectResponse
    {
        $this->authorize('create', Prova::class);

        $dto   = CriarProvaDTO::fromArray($request->validated(), $request->user()->id);
        $prova = $action->execute($dto);

        return redirect()->route('provas.show', $prova)
            ->with('success', 'Prova criada com sucesso!');
    }

    public function show(Prova $prova): View
    {
        $this->authorize('view', $prova);
        $prova->load(['questoes', 'gabaritoAtivo', 'cartoes']);
        return view('provas.show', compact('prova'));
    }

    public function edit(Prova $prova): View
    {
        $this->authorize('update', $prova);
        return view('provas.edit', compact('prova'));
    }

    public function update(ProvaRequest $request, Prova $prova): RedirectResponse
    {
        $this->authorize('update', $prova);

        $prova->update($request->validated());

        return redirect()->route('provas.show', $prova)
            ->with('success', 'Prova atualizada!');
    }

    public function destroy(Prova $prova): RedirectResponse
    {
        $this->authorize('delete', $prova);
        $prova->delete();

        return redirect()->route('provas.index')
            ->with('success', 'Prova excluída!');
    }

    public function relatorio(Prova $prova): View
    {
        $this->authorize('view', $prova);

        $resultados = Resultado::query()
            ->with(['leitura.cartao'])
            ->whereHas('leitura.cartao', fn ($q) => $q->where('prova_id', $prova->id))
            ->orderByDesc('nota_final')
            ->get();

        $stats = [
            'total'    => $resultados->count(),
            'media'    => round($resultados->avg('nota_final') ?? 0, 2),
            'maxima'   => $resultados->max('nota_final') ?? 0,
            'minima'   => $resultados->min('nota_final') ?? 0,
            'aprovados'=> $resultados->filter(fn ($r) => $r->percentual_acerto >= 60)->count(),
        ];

        // Histograma por faixas de nota
        $faixas = ['0-2' => 0, '2-4' => 0, '4-6' => 0, '6-8' => 0, '8-10' => 0];
        foreach ($resultados as $r) {
            $nota = (float) $r->nota_final;
            $max  = (float) $prova->nota_maxima;
            $pct  = $max > 0 ? ($nota / $max) * 10 : 0;
            match(true) {
                $pct < 2  => $faixas['0-2']++,
                $pct < 4  => $faixas['2-4']++,
                $pct < 6  => $faixas['4-6']++,
                $pct < 8  => $faixas['6-8']++,
                default   => $faixas['8-10']++,
            };
        }

        // Frequência de acerto por questão
        $acertosPorQuestao = [];
        if ($resultados->isNotEmpty()) {
            for ($q = 1; $q <= $prova->total_questoes; $q++) {
                $acertos = 0;
                $total   = 0;
                foreach ($resultados as $r) {
                    $detalhe = $r->detalhe_questoes[$q] ?? null;
                    if ($detalhe) {
                        $total++;
                        if ($detalhe['status'] === 'acerto') $acertos++;
                    }
                }
                $acertosPorQuestao[$q] = $total > 0 ? round($acertos / $total * 100) : null;
            }
        }

        return view('provas.relatorio', compact('prova', 'resultados', 'stats', 'faixas', 'acertosPorQuestao'));
    }
}
