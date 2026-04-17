<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Proesc Simulados'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen">

<nav class="bg-indigo-700 text-white px-6 py-3 flex items-center justify-between shadow">
    <a href="{{ route('provas.index') }}" class="font-bold text-lg tracking-wide">Proesc Simulados</a>
    <div class="flex items-center gap-5 text-sm">
        <a href="{{ route('provas.index') }}" class="hover:text-indigo-200">Provas</a>
        <a href="{{ route('leitura.index') }}" class="hover:text-indigo-200">Leitura</a>
        <a href="{{ route('resultados.index') }}" class="hover:text-indigo-200">Resultados</a>
        @auth
        <span class="opacity-60 text-xs">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button class="hover:text-indigo-200 text-sm">Sair</button>
        </form>
        @endauth
    </div>
</nav>

<main class="max-w-6xl mx-auto px-4 py-8">
    @if (session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    @yield('content')
</main>

@livewireScripts
@stack('scripts')
</body>
</html>
