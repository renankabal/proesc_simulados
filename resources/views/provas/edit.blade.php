@extends('layouts.main')

@section('title', 'Editar Prova')

@section('content')
<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Editar Prova</h1>

    <form action="{{ route('provas.update', $prova) }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        @method('PUT')
        @include('provas._form')

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('provas.show', $prova) }}" class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">Salvar</button>
        </div>
    </form>
</div>
@endsection
