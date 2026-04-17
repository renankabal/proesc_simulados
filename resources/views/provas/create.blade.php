@extends('layouts.main')

@section('title', 'Nova Prova')

@section('content')
<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Nova Prova</h1>

    <form action="{{ route('provas.store') }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        @include('provas._form')

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('provas.index') }}" class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">Criar Prova</button>
        </div>
    </form>
</div>
@endsection
