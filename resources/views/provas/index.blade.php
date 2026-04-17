@extends('layouts.main')

@section('title', 'Provas')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Provas</h1>
    @can('create', \App\Domain\Prova\Models\Prova::class)
    <a href="{{ route('provas.create') }}" class="bg-green-600 text-white px-5 py-2 rounded-full hover:bg-green-700">
        + Nova Prova
    </a>
    @endcan
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    @forelse ($provas as $prova)
    <div class="border-b last:border-0 px-6 py-4 flex items-center justify-between hover:bg-gray-50">
        <div>
            <p class="font-semibold text-gray-800">{{ $prova->titulo }}</p>
            <p class="text-sm text-gray-500">
                {{ $prova->disciplina }} • {{ $prova->turma }} •
                {{ $prova->total_questoes }} questões •
                <span class="font-medium {{ $prova->status->value === 'publicada' ? 'text-green-600' : 'text-gray-500' }}">
                    {{ $prova->status->label() }}
                </span>
            </p>
        </div>
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('provas.show', $prova) }}" class="text-green-600 hover:underline">Ver</a>
            <a href="{{ route('provas.edit', $prova) }}" class="text-yellow-600 hover:underline">Editar</a>
            <a href="{{ route('provas.gabarito.edit', $prova) }}" class="text-green-600 hover:underline">Gabarito</a>
            <a href="{{ route('cartoes.index', $prova) }}" class="text-purple-600 hover:underline">Cartões</a>
        </div>
    </div>
    @empty
    <div class="px-6 py-10 text-center text-gray-400">Nenhuma prova cadastrada ainda.</div>
    @endforelse
</div>

<div class="mt-4">{{ $provas->links() }}</div>
@endsection
