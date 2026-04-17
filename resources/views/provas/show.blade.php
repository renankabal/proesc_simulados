@extends('layouts.main')

@section('title', $prova->titulo)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">{{ $prova->titulo }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $prova->disciplina }} • {{ $prova->turma }} •
            {{ $prova->total_questoes }} questões •
            <span class="{{ $prova->status->value === 'publicada' ? 'text-green-600' : 'text-gray-500' }} font-medium">
                {{ $prova->status->label() }}
            </span>
        </p>
    </div>
    <div class="flex gap-2 text-sm">
        <a href="{{ route('provas.edit', $prova) }}" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Editar</a>
        <a href="{{ route('provas.gabarito.edit', $prova) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Gabarito</a>
        <a href="{{ route('cartoes.index', $prova) }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Cartões</a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Nota Máxima</p>
        <p class="text-2xl font-bold text-indigo-700">{{ number_format($prova->nota_maxima, 1) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Cartões Gerados</p>
        <p class="text-2xl font-bold text-indigo-700">{{ $prova->cartoes->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Gabarito</p>
        <p class="text-sm font-medium {{ $prova->gabaritoAtivo ? 'text-green-600' : 'text-red-500' }}">
            {{ $prova->gabaritoAtivo ? 'Configurado (v' . $prova->gabaritoAtivo->versao . ')' : 'Não configurado' }}
        </p>
    </div>
</div>

@if ($prova->gabaritoAtivo)
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="font-semibold text-gray-700 mb-3">Gabarito Ativo (v{{ $prova->gabaritoAtivo->versao }})</h2>
    <div class="flex flex-wrap gap-2">
        @foreach ($prova->gabaritoAtivo->respostas as $num => $letra)
        <span class="inline-flex items-center gap-1 text-xs font-medium bg-indigo-50 text-indigo-700 px-2 py-1 rounded">
            <span class="text-gray-400">{{ $num }}.</span> {{ $letra }}
        </span>
        @endforeach
    </div>
</div>
@endif

<div class="flex justify-end">
    <form action="{{ route('provas.destroy', $prova) }}" method="POST"
        onsubmit="return confirm('Tem certeza que deseja excluir esta prova?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-500 text-sm hover:underline">Excluir prova</button>
    </form>
</div>
@endsection
