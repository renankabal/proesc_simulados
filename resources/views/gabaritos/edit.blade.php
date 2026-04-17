@extends('layouts.main')

@section('title', 'Gabarito — ' . $prova->titulo)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gabarito — {{ $prova->titulo }}</h1>
        <a href="{{ route('provas.show', $prova) }}" class="text-sm text-gray-500 hover:underline">← Voltar</a>
    </div>

    @if ($prova->gabaritoAtivo)
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded text-sm">
        Gabarito ativo: versão {{ $prova->gabaritoAtivo->versao }}
    </div>
    @endif

    <form action="{{ route('provas.gabarito.update', $prova) }}" method="POST"
          class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        <p class="text-sm text-gray-500 mb-4">Preencha a resposta correta para cada questão:</p>

        <div class="grid grid-cols-5 sm:grid-cols-10 gap-3">
            @for ($i = 1; $i <= $prova->total_questoes; $i++)
            @php $atual = $prova->gabaritoAtivo?->respostas[(string)$i] ?? '' @endphp
            <div class="text-center">
                <p class="text-xs text-gray-400 mb-1">{{ $i }}</p>
                <select name="respostas[{{ $i }}]"
                    class="w-full border rounded px-1 py-1 text-sm text-center focus:ring-indigo-400">
                    <option value="">-</option>
                    @foreach (['A','B','C','D','E'] as $letra)
                    <option value="{{ $letra }}" {{ $atual === $letra ? 'selected' : '' }}>{{ $letra }}</option>
                    @endforeach
                </select>
            </div>
            @endfor
        </div>

        <div class="flex justify-end gap-3 pt-6">
            <a href="{{ route('provas.show', $prova) }}" class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">Salvar Gabarito</button>
        </div>
    </form>
</div>
@endsection
