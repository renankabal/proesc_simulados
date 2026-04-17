@extends('layouts.main')

@section('title', 'Resultados')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">Resultados</h1>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-4 py-3 text-left">Aluno</th>
                <th class="px-4 py-3 text-left">Prova</th>
                <th class="px-4 py-3 text-right">Acertos</th>
                <th class="px-4 py-3 text-right">Nota</th>
                <th class="px-4 py-3 text-right">%</th>
                <th class="px-4 py-3 text-left">Data</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($resultados as $resultado)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $resultado->leitura->cartao->codigo_aluno }}</p>
                    <p class="text-gray-400 text-xs">{{ $resultado->leitura->cartao->nome_aluno }}</p>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $resultado->leitura->cartao->prova->titulo }}</td>
                <td class="px-4 py-3 text-right font-medium">
                    {{ $resultado->total_acertos }}/{{ $resultado->total_questoes }}
                </td>
                <td class="px-4 py-3 text-right font-bold text-indigo-700">
                    {{ number_format($resultado->nota_final, 1) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-600">
                    {{ $resultado->percentualFormatado() }}
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                    {{ $resultado->calculado_em?->format('d/m/Y H:i') }}
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Nenhum resultado ainda.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $resultados->links() }}</div>
@endsection
