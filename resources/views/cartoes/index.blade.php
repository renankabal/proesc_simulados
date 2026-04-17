@extends('layouts.main')

@section('title', 'Cartões — ' . $prova->titulo)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Cartões-Resposta</h1>
        <p class="text-sm text-gray-500">{{ $prova->titulo }}</p>
    </div>
    <a href="{{ route('provas.show', $prova) }}" class="text-sm text-gray-500 hover:underline">← Voltar</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="font-semibold text-gray-700 mb-4">Gerar Novo Cartão</h2>
        <form action="{{ route('cartoes.store', $prova) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Código do Aluno *</label>
                <input type="text" name="codigo_aluno" required
                    class="mt-1 w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Nome do Aluno</label>
                <input type="text" name="nome_aluno"
                    class="mt-1 w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Turma</label>
                <input type="text" name="turma" value="{{ $prova->turma }}"
                    class="mt-1 w-full border rounded px-3 py-2 text-sm">
            </div>
            <button type="submit" class="w-full bg-purple-600 text-white py-2.5 rounded-full hover:bg-purple-700 text-sm font-semibold">
                Gerar e Baixar PDF
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="font-semibold text-gray-700 mb-4">Gerar em Lote</h2>
        <form action="{{ route('cartoes.lote', $prova) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">Alunos (um por linha)</label>
                <p class="text-xs text-gray-400 mb-1">Formato: <code>codigo,nome</code> ou só <code>codigo</code></p>
                <textarea name="alunos" rows="6" required placeholder="ALU001,João Silva&#10;ALU002,Maria Santos&#10;ALU003"
                    class="mt-1 w-full border rounded px-3 py-2 text-sm font-mono"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Turma</label>
                    <input type="text" name="turma" value="{{ $prova->turma }}"
                        class="mt-1 w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tentativa</label>
                    <input type="number" name="tentativa" value="1" min="1"
                        class="mt-1 w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-2.5 rounded-full hover:bg-green-700 text-sm font-semibold">
                Gerar Lote e Baixar PDF
            </button>
        </form>

        <div class="mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total de Cartões</p>
            <p class="text-3xl font-bold text-green-700">{{ $cartoes->total() }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-4 py-3 text-left">Aluno</th>
                <th class="px-4 py-3 text-left">Turma</th>
                <th class="px-4 py-3 text-left">Tentativa</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($cartoes as $cartao)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $cartao->codigo_aluno }}</p>
                    <p class="text-gray-400 text-xs">{{ $cartao->nome_aluno }}</p>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $cartao->turma ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $cartao->tentativa }}</td>
                <td class="px-4 py-3">
                    @if ($cartao->leitura?->isConfirmada())
                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">Lido</span>
                    @elseif ($cartao->leitura)
                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs">{{ $cartao->leitura->status->label() }}</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-xs">Pendente</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('cartoes.pdf', [$prova, $cartao]) }}" class="text-purple-600 hover:underline">PDF</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Nenhum cartão gerado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $cartoes->links() }}</div>
@endsection
