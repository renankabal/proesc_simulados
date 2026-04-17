@extends('layouts.main')

@section('title', $prova->titulo)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('provas.index') }}" class="text-sm text-gray-400 hover:underline">← Provas</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-1">{{ $prova->titulo }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ $prova->disciplina }} • {{ $prova->turma }} •
            {{ $prova->total_questoes }} questões •
            <span class="{{ $prova->status->value === 'publicada' ? 'text-green-600' : 'text-gray-400' }} font-medium">
                {{ $prova->status->label() }}
            </span>
        </p>
    </div>
    <div class="flex gap-2 text-sm flex-wrap justify-end">
        <a href="{{ route('provas.edit', $prova) }}" class="bg-yellow-500 text-white px-3 py-2 rounded hover:bg-yellow-600">Editar</a>
        <a href="{{ route('provas.gabarito.edit', $prova) }}" class="bg-green-600 text-white px-3 py-2 rounded hover:bg-green-700">Gabarito</a>
        <a href="{{ route('cartoes.index', $prova) }}" class="bg-purple-600 text-white px-3 py-2 rounded hover:bg-purple-700">Cartões</a>
        <a href="{{ route('provas.relatorio', $prova) }}" class="bg-indigo-600 text-white px-3 py-2 rounded hover:bg-indigo-700">Relatório</a>
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Nota Máxima</p>
        <p class="text-2xl font-bold text-indigo-700">{{ number_format($prova->nota_maxima, 1) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Cartões</p>
        <p class="text-2xl font-bold text-indigo-700">{{ $prova->cartoes->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Questões</p>
        <p class="text-2xl font-bold text-indigo-700">{{ $prova->questoes->count() }}/{{ $prova->total_questoes }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Gabarito</p>
        <p class="text-sm font-medium mt-1 {{ $prova->gabaritoAtivo ? 'text-green-600' : 'text-red-500' }}">
            {{ $prova->gabaritoAtivo ? '✓ v' . $prova->gabaritoAtivo->versao : '✗ Não configurado' }}
        </p>
    </div>
</div>

{{-- QUESTÕES --}}
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-700">Questões — Anulação e Peso</h2>
        <span class="text-xs text-gray-400">Marque para anular • Ajuste o peso (padrão 1.00)</span>
    </div>
    <form action="{{ route('questoes.bulk', $prova) }}" method="POST">
        @csrf
        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2 mb-4">
            @for ($i = 1; $i <= $prova->total_questoes; $i++)
            @php $q = $prova->questoes->firstWhere('numero', $i); @endphp
            <div class="border rounded p-2 text-center {{ $q?->anulada ? 'bg-red-50 border-red-300' : 'bg-gray-50' }}">
                <input type="hidden" name="questoes[{{ $i - 1 }}][numero]" value="{{ $i }}">
                <p class="text-xs font-bold text-gray-600 mb-1">Q{{ $i }}</p>
                <label class="flex items-center justify-center gap-1 text-xs cursor-pointer select-none">
                    <input type="checkbox" name="questoes[{{ $i - 1 }}][anulada]" value="1"
                        {{ $q?->anulada ? 'checked' : '' }} class="w-3 h-3 accent-red-500">
                    <span class="text-gray-500">Anular</span>
                </label>
                <input type="number" name="questoes[{{ $i - 1 }}][peso]"
                    value="{{ number_format($q?->peso ?? 1.00, 2, '.', '') }}"
                    step="0.01" min="0.01"
                    class="mt-1 w-full border rounded text-center text-xs py-0.5" title="Peso">
            </div>
            @endfor
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">
                Salvar Questões
            </button>
        </div>
    </form>
</div>

@if ($prova->gabaritoAtivo)
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="font-semibold text-gray-700 mb-3">Gabarito Ativo (v{{ $prova->gabaritoAtivo->versao }})</h2>
    <div class="flex flex-wrap gap-2">
        @foreach ($prova->gabaritoAtivo->respostas as $num => $letra)
        @php $anulada = $prova->questoes->firstWhere('numero', (int)$num)?->anulada; @endphp
        <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded
            {{ $anulada ? 'bg-red-100 text-red-400 line-through' : 'bg-indigo-50 text-indigo-700' }}">
            <span class="text-gray-400">{{ $num }}.</span> {{ $letra }}
        </span>
        @endforeach
    </div>
</div>
@endif

<div class="flex justify-end">
    <form action="{{ route('provas.destroy', $prova) }}" method="POST"
        onsubmit="return confirm('Excluir esta prova permanentemente?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-400 text-sm hover:text-red-600 hover:underline">Excluir prova</button>
    </form>
</div>
@endsection
