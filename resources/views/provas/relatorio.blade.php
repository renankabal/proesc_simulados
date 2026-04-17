@extends('layouts.main')

@section('title', 'Relatório — ' . $prova->titulo)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('provas.show', $prova) }}" class="text-sm text-gray-400 hover:underline">← {{ $prova->titulo }}</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-1">Relatório da Prova</h1>
    </div>
    <a href="{{ route('resultados.export', ['prova_id' => $prova->id]) }}"
       class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">Exportar CSV</a>
</div>

@if ($stats['total'] === 0)
<div class="bg-white rounded-lg shadow p-10 text-center text-gray-400">
    Nenhuma leitura confirmada para esta prova ainda.
</div>
@else

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Leituras</p>
        <p class="text-3xl font-bold text-indigo-700">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Média</p>
        <p class="text-3xl font-bold text-indigo-700">{{ number_format($stats['media'], 1) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Maior</p>
        <p class="text-3xl font-bold text-green-600">{{ number_format($stats['maxima'], 1) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Menor</p>
        <p class="text-3xl font-bold text-red-500">{{ number_format($stats['minima'], 1) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Aprovados ≥60%</p>
        <p class="text-3xl font-bold text-green-600">{{ $stats['aprovados'] }}</p>
        <p class="text-xs text-gray-400">de {{ $stats['total'] }}</p>
    </div>
</div>

{{-- Histograma --}}
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="font-semibold text-gray-700 mb-4">Distribuição de Notas</h2>
    @php $maxFaixa = max(array_values($faixas)) ?: 1; @endphp
    <div class="flex items-end gap-3 h-32">
        @foreach ($faixas as $label => $count)
        @php $altura = round($count / $maxFaixa * 100); @endphp
        <div class="flex-1 flex flex-col items-center gap-1">
            <span class="text-xs font-bold text-gray-600">{{ $count }}</span>
            <div class="w-full bg-indigo-500 rounded-t transition-all"
                 style="height: {{ $altura }}%"></div>
            <span class="text-xs text-gray-400">{{ $label }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- Acerto por questão --}}
@if (!empty($acertosPorQuestao))
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="font-semibold text-gray-700 mb-4">Taxa de Acerto por Questão</h2>
    <div class="flex flex-wrap gap-2">
        @foreach ($acertosPorQuestao as $num => $pct)
        @php
            $cor = $pct === null ? 'bg-gray-100 text-gray-400'
                 : ($pct >= 70 ? 'bg-green-100 text-green-700'
                 : ($pct >= 40 ? 'bg-yellow-100 text-yellow-700'
                 : 'bg-red-100 text-red-600'));
        @endphp
        <div class="text-center px-3 py-2 rounded {{ $cor }}">
            <p class="text-xs font-bold">Q{{ $num }}</p>
            <p class="text-sm font-semibold">{{ $pct !== null ? $pct . '%' : '-' }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Ranking --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left">Aluno</th>
                <th class="px-4 py-3 text-center">Acertos</th>
                <th class="px-4 py-3 text-right">Nota</th>
                <th class="px-4 py-3 text-right">%</th>
                <th class="px-4 py-3 text-left"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach ($resultados as $pos => $resultado)
            @php $pct = (float) $resultado->percentual_acerto; @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-400 font-medium">{{ $pos + 1 }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $resultado->leitura->cartao->codigo_aluno }}</p>
                    <p class="text-xs text-gray-400">{{ $resultado->leitura->cartao->nome_aluno }}</p>
                </td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $resultado->total_acertos }}/{{ $resultado->total_questoes }}</td>
                <td class="px-4 py-3 text-right font-bold {{ $pct >= 60 ? 'text-green-700' : 'text-red-500' }}">
                    {{ number_format($resultado->nota_final, 1) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-500 text-xs">{{ number_format($pct, 1) }}%</td>
                <td class="px-4 py-3">
                    <a href="{{ route('resultados.show', $resultado) }}" class="text-indigo-500 hover:underline text-xs">Ver detalhe</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
