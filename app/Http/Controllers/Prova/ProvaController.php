<?php

namespace App\Http\Controllers\Prova;

use App\Application\Actions\Prova\CriarProvaAction;
use App\Application\DTOs\CriarProvaDTO;
use App\Domain\Prova\Enums\StatusProva;
use App\Domain\Prova\Models\Prova;
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
        $prova->load('questoes', 'gabaritoAtivo', 'cartoes');
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
}
