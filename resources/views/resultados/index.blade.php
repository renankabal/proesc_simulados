@extends('layouts.main')

@section('title', 'Resultados')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Resultados</h1>
    <a href="{{ route('resultados.export', request()->query()) }}"
       class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
        Exportar CSV
    </a>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('resultados.index') }}" class="bg-white rounded-lg shadow p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
        <label class="block text-xs text-gray-500 mb-1">Prova</label>
        <select name="prova_id" class="w-full border rounded px-3 py-2 text-sm">
            <option value="">Todas as provas</option>
            @foreach ($provas as $id => $titulo)
            <option value="{{ $id }}" {{ request('prova_id') == $id ? 'selected' : '' }}>{{ $titulo }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex-1 min-w-48">
        <label class="block text-xs text-gray-500 mb-1">Buscar aluno</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Código ou nome..."
            class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div class="flex gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">Filtrar</button>
        <a href="{{ route('resultados.index') }}" class="border px-4 py-2 rounded text-sm text-gray-600 hover:bg-gray-50">Limpar</a>
    </div>
</form>

{{-- Estatísticas rápidas --}}
@if ($resultados->total() > 0)
@php
    $media   = $resultados->getCollection()->avg('nota_final');
    $maxNota = $resultados->getCollection()->max('nota_final');
    $minNota = $resultados->getCollection()->min('nota_final');
@endphp
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Média da Página</p>
        <p class="text-2xl font-bold text-indigo-700">{{ number_format($media, 1) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Maior Nota</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($maxNota, 1) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Menor Nota</p>
        <p class="text-2xl font-bold text-red-500">{{ number_format($minNota, 1) }}</p>
    </div>
</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-4 py-3 text-left">Aluno</th>
                <th class="px-4 py-3 text-left">Prova</th>
                <th class="px-4 py-3 text-center">Acertos</th>
                <th class="px-4 py-3 text-right">Nota</th>
                <th class="px-4 py-3 text-right">%</th>
                <th class="px-4 py-3 text-left hidden md:table-cell">Data</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($resultados as $resultado)
            @php
                $pct = (float) $resultado->percentual_acerto;
                $cor = $pct >= 70 ? 'text-green-700 bg-green-50' : ($pct >= 50 ? 'text-yellow-700 bg-yellow-50' : 'text-red-700 bg-red-50');
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $resultado->leitura->cartao->codigo_aluno }}</p>
                    @if ($resultado->leitura->cartao->nome_aluno)
                    <p class="text-gray-400 text-xs">{{ $resultado->leitura->cartao->nome_aluno }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-700 text-xs">{{ Str::limit($resultado->leitura->cartao->prova->titulo, 40) }}</td>
                <td class="px-4 py-3 text-center text-gray-600 font-medium">
                    {{ $resultado->total_acertos }}/{{ $resultado->total_questoes }}
                </td>
                <td class="px-4 py-3 text-right">
                    <span class="font-bold text-lg {{ $pct >= 70 ? 'text-green-700' : ($pct >= 50 ? 'text-yellow-700' : 'text-red-600') }}">
                        {{ number_format($resultado->nota_final, 1) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ $cor }}">
                        {{ number_format($resultado->percentual_acerto, 1) }}%
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-400 text-xs hidden md:table-cell">
                    {{ $resultado->calculado_em?->format('d/m/Y H:i') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                    Nenhum resultado encontrado.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 flex items-center justify-between">
    <p class="text-sm text-gray-500">{{ $resultados->total() }} resultado(s)</p>
    {{ $resultados->links() }}
</div>
@endsection
