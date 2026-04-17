<?php

namespace App\Http\Controllers\Prova;

use App\Domain\Prova\Models\Prova;
use App\Domain\Prova\Models\Questao;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuestaoController extends Controller
{
    public function store(Request $request, Prova $prova): RedirectResponse
    {
        $this->authorize('update', $prova);

        $data = $request->validate([
            'numero'  => ['required', 'integer', 'min:1', 'max:90'],
            'anulada' => ['boolean'],
            'peso'    => ['nullable', 'numeric', 'min:0.01', 'max:99'],
        ]);

        $prova->questoes()->updateOrCreate(
            ['numero' => $data['numero']],
            ['anulada' => $data['anulada'] ?? false, 'peso' => $data['peso'] ?? 1.00]
        );

        return back()->with('success', "Questão {$data['numero']} salva.");
    }

    public function bulkStore(Request $request, Prova $prova): RedirectResponse
    {
        $this->authorize('update', $prova);

        $data = $request->validate([
            'questoes'          => ['required', 'array'],
            'questoes.*.numero' => ['required', 'integer', 'min:1', 'max:90'],
            'questoes.*.anulada'=> ['nullable', 'boolean'],
            'questoes.*.peso'   => ['nullable', 'numeric', 'min:0.01', 'max:99'],
        ]);

        foreach ($data['questoes'] as $q) {
            $prova->questoes()->updateOrCreate(
                ['numero' => $q['numero']],
                ['anulada' => (bool) ($q['anulada'] ?? false), 'peso' => $q['peso'] ?? 1.00]
            );
        }

        return back()->with('success', 'Questões salvas com sucesso!');
    }

    public function destroy(Prova $prova, Questao $questao): RedirectResponse
    {
        $this->authorize('update', $prova);

        abort_if($questao->prova_id !== $prova->id, 404);
        $questao->delete();

        return back()->with('success', "Questão {$questao->numero} removida.");
    }
}
