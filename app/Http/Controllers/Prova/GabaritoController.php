<?php

namespace App\Http\Controllers\Prova;

use App\Application\Actions\Gabarito\SalvarGabaritoAction;
use App\Domain\Prova\Models\Prova;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GabaritoController extends Controller
{
    public function edit(Prova $prova): View
    {
        $this->authorize('update', $prova);
        $prova->load('questoes', 'gabaritoAtivo');
        return view('gabaritos.edit', compact('prova'));
    }

    public function update(Request $request, Prova $prova, SalvarGabaritoAction $action): RedirectResponse
    {
        $this->authorize('update', $prova);

        $respostas = $request->validate([
            'respostas'   => ['required', 'array'],
            'respostas.*' => ['nullable', 'string', 'in:A,B,C,D,E'],
        ])['respostas'];

        $action->execute($prova, $respostas, $request->user()->id);

        return redirect()->route('provas.show', $prova)
            ->with('success', 'Gabarito salvo!');
    }
}
