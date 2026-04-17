@extends('layouts.main')

@section('title', 'Resultado — ' . $resultado->leitura->cartao->codigo_aluno)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('provas.relatorio', $prova) }}" class="text-sm text-gray-400 hover:underline">← Relatório da prova</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-1">Resultado Individual</h1>
        <p class="text-sm text-gray-500">
            {{ $resultado->leitura->cartao->codigo_aluno }}
            @if ($resultado->leitura->cartao->nome_aluno)
                — {{ $resultado->leitura->cartao->nome_aluno }}
            @endif
            &bull; {{ $prova->titulo }}
        </p>
    </div>
    <a href="{{ route('resultados.export', ['prova_id' => $prova->id]) }}"
       class="bg-green-600 text-white px-5 py-2 rounded-full text-sm hover:bg-green-700">CSV da Prova</a>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-5 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Nota Final</p>
        <p class="text-4xl font-bold {{ (float)$resultado->percentual_acerto >= 60 ? 'text-green-600' : 'text-red-500' }} mt-1">
            {{ number_format($resultado->nota_final, 1) }}
        </p>
        <p class="text-xs text-gray-400">de {{ number_format($prova->nota_maxima, 1) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Aproveitamento</p>
        <p class="text-4xl font-bold text-green-700 mt-1">{{ number_format($resultado->percentual_acerto, 1) }}%</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Acertos / Total</p>
        <p class="text-3xl font-bold text-gray-700 mt-1">{{ $resultado->total_acertos }}/{{ $resultado->total_questoes }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Resumo</p>
        <div class="space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-green-600">Acertos</span><span class="font-bold">{{ $resultado->total_acertos }}</span></div>
            <div class="flex justify-between"><span class="text-red-500">Erros</span><span class="font-bold">{{ $resultado->total_erros }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Em branco</span><span class="font-bold">{{ $resultado->total_brancos }}</span></div>
            <div class="flex justify-between"><span class="text-orange-400">Anuladas</span><span class="font-bold">{{ $resultado->total_anuladas }}</span></div>
        </div>
    </div>
</div>

{{-- Detalhe por questão --}}
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="font-semibold text-gray-700 mb-4">Detalhe por Questão</h2>
    <div class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-10 gap-2">
        @foreach ($questoes as $num => $q)
        @php
            $bg = match($q['status']) {
                'acerto'  => 'bg-green-100 border-green-300 text-green-800',
                'erro'    => 'bg-red-100 border-red-300 text-red-700',
                'branco'  => 'bg-gray-100 border-gray-200 text-gray-400',
                'anulada' => 'bg-orange-100 border-orange-200 text-orange-600',
                'dupla'   => 'bg-yellow-100 border-yellow-300 text-yellow-700',
                default   => 'bg-gray-50 border-gray-200 text-gray-400',
            };
        @endphp
        <div class="border rounded p-2 text-center {{ $bg }}">
            <p class="text-xs font-bold text-gray-500">Q{{ $num }}</p>
            <p class="text-lg font-bold leading-tight">{{ $q['marcacao'] ?? '—' }}</p>
            <p class="text-xs opacity-70">↓{{ $q['gabarito'] ?? '?' }}</p>
            <p class="text-xs font-medium mt-0.5">
                @if ($q['status'] === 'acerto') ✓
                @elseif ($q['status'] === 'erro') ✗
                @elseif ($q['status'] === 'branco') ○
                @elseif ($q['status'] === 'anulada') ∅
                @elseif ($q['status'] === 'dupla') !!
                @endif
            </p>
        </div>
        @endforeach
    </div>

    <div class="flex gap-4 mt-4 text-xs text-gray-500">
        <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-200 rounded inline-block"></span> Acerto</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 bg-red-200 rounded inline-block"></span> Erro</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 bg-gray-200 rounded inline-block"></span> Branco</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 bg-orange-200 rounded inline-block"></span> Anulada</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 bg-yellow-200 rounded inline-block"></span> Dupla marcação</span>
    </div>
</div>

<div class="mt-4 text-xs text-gray-400 text-right">
    Calculado em: {{ $resultado->calculado_em?->format('d/m/Y H:i') }}
    @if ($resultado->recalculado_em)
        &bull; Recalculado em: {{ $resultado->recalculado_em->format('d/m/Y H:i') }}
    @endif
</div>
@endsection
