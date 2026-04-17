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
use Illuminate\Support\Facades\Storage;
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

        // Gera PDF e salva path
        $cartao = $this->gerarEsalvarPdf($prova, $cartao);

        return redirect()->route('cartoes.pdf', [$prova, $cartao])
            ->with('success', 'Cartão gerado!');
    }

    public function storeLote(Request $request, Prova $prova, GerarCartaoAction $action): RedirectResponse
    {
        $this->authorize('update', $prova);

        $data = $request->validate([
            'alunos'          => ['required', 'string'],
            'turma'           => ['nullable', 'string', 'max:100'],
            'tentativa'       => ['nullable', 'integer', 'min:1'],
        ]);

        // Cada linha: "codigo_aluno,nome_aluno" ou só "codigo_aluno"
        $linhas   = array_filter(array_map('trim', explode("\n", $data['alunos'])));
        $cartoes  = [];

        foreach ($linhas as $linha) {
            $partes       = explode(',', $linha, 2);
            $codigoAluno  = trim($partes[0]);
            $nomeAluno    = isset($partes[1]) ? trim($partes[1]) : null;

            if (empty($codigoAluno)) continue;

            $dto    = GerarCartaoDTO::fromArray([
                'prova_id'     => $prova->id,
                'codigo_aluno' => $codigoAluno,
                'nome_aluno'   => $nomeAluno,
                'turma'        => $data['turma'] ?? $prova->turma,
                'tentativa'    => $data['tentativa'] ?? 1,
            ], $request->user()->id);

            $cartao    = $action->execute($dto);
            $cartoes[] = $this->gerarEsalvarPdf($prova, $cartao);
        }

        if (count($cartoes) === 1) {
            return redirect()->route('cartoes.pdf', [$prova, $cartoes[0]])
                ->with('success', 'Cartão gerado!');
        }

        // Múltiplos: gera PDF único com todos os cartões
        $prova->load('gabaritoAtivo');
        $pdf = Pdf::loadView('cartoes.pdf_lote', compact('prova', 'cartoes'))
            ->setPaper('a4', 'portrait');

        $filename = "cartoes-{$prova->id}-" . now()->format('YmdHis') . '.pdf';
        $path     = "cartoes_lote/{$filename}";
        Storage::disk('local')->put($path, $pdf->output());

        return redirect()->route('cartoes.index', $prova)
            ->with('success', count($cartoes) . ' cartões gerados! <a href="' . route('cartoes.downloadLote', [$prova, $filename]) . '" class="underline font-bold">Baixar PDF</a>');
    }

    public function pdf(Prova $prova, CartaoResposta $cartao): Response
    {
        $this->authorize('view', $prova);

        // Se já tem PDF salvo em disco, serve direto
        if ($cartao->pdf_path && Storage::disk('local')->exists($cartao->pdf_path)) {
            return response(Storage::disk('local')->get($cartao->pdf_path), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="cartao-' . $cartao->codigo_aluno . '.pdf"',
            ]);
        }

        $prova->load('gabaritoAtivo');
        $pdf = Pdf::loadView('cartoes.pdf', compact('prova', 'cartao'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("cartao-{$cartao->codigo_aluno}.pdf");
    }

    public function downloadLote(Prova $prova, string $filename): Response
    {
        $this->authorize('view', $prova);
        $path = "cartoes_lote/{$filename}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function gerarEsalvarPdf(Prova $prova, CartaoResposta $cartao): CartaoResposta
    {
        $prova->loadMissing('gabaritoAtivo');
        $pdf  = Pdf::loadView('cartoes.pdf', compact('prova', 'cartao'))->setPaper('a4', 'portrait');
        $path = "cartoes/{$prova->id}/{$cartao->id}.pdf";
        Storage::disk('local')->put($path, $pdf->output());
        $cartao->update(['pdf_path' => $path]);
        return $cartao;
    }
}
