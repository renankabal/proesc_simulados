<?php

namespace App\Http\Controllers\Cartao;

use App\Application\Actions\Cartao\GerarCartaoAction;
use App\Application\DTOs\GerarCartaoDTO;
use App\Domain\Cartao\Models\CartaoResposta;
use App\Domain\Prova\Models\Prova;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CartaoRespostaController extends Controller
{
    public function index(Prova $prova): View
    {
        $this->authorize('view', $prova);
        $cartoes = $prova->cartoes()->with('leitura')->orderByDesc('created_at')->paginate(20);
        return view('cartoes.index', compact('prova', 'cartoes'));
    }

    public function store(Request $request, Prova $prova, GerarCartaoAction $action): RedirectResponse
    {
        $this->authorize('update', $prova);

        $data = $request->validate([
            'codigo_aluno' => ['required', 'string', 'max:100'],
            'nome_aluno'   => ['nullable', 'string', 'max:255'],
            'turma'        => ['nullable', 'string', 'max:100'],
            'tentativa'    => ['nullable', 'integer', 'min:1'],
        ]);

        $dto    = GerarCartaoDTO::fromArray(array_merge($data, ['prova_id' => $prova->id]), $request->user()->id);
        $cartao = $action->execute($dto);

        return redirect()->route('cartoes.pdf', [$prova, $cartao])
            ->with('success', 'Cartão gerado!');
    }

    public function pdf(Prova $prova, CartaoResposta $cartao): Response
    {
        $this->authorize('view', $prova);
        $prova->load('gabaritoAtivo');

        $pdf = Pdf::loadView('cartoes.pdf', compact('prova', 'cartao'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("cartao-{$cartao->codigo_aluno}.pdf");
    }
}
