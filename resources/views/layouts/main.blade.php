<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Proesc Simulados')) — Proesc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen">

<nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-16">
        <a href="{{ route('provas.index') }}" class="flex items-center gap-2 group">
            <span class="text-green-600 font-black text-xl tracking-tight group-hover:text-green-700 transition-colors">proesc</span>
            <span class="text-gray-300 font-light text-lg">|</span>
            <span class="text-gray-500 text-sm font-semibold tracking-wide">simulados</span>
        </a>
        <div class="flex items-center gap-6 text-sm font-medium">
            <a href="{{ route('provas.index') }}" class="text-gray-600 hover:text-green-600 transition-colors">Provas</a>
            <a href="{{ route('leitura.index') }}" class="text-gray-600 hover:text-green-600 transition-colors">Leitura</a>
            <a href="{{ route('resultados.index') }}" class="text-gray-600 hover:text-green-600 transition-colors">Resultados</a>
            @auth
            <span class="text-gray-400 text-xs border-l border-gray-200 pl-4">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button class="text-gray-400 hover:text-red-500 transition-colors text-sm">Sair</button>
            </form>
            @endauth
        </div>
    </div>
</nav>

<main class="max-w-6xl mx-auto px-4 py-8">
    @if (session('success'))
        <div class="mb-5 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl flex items-center gap-2 text-sm shadow-sm">
            <span class="text-green-500 font-bold text-base">✓</span> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl flex items-center gap-2 text-sm shadow-sm">
            <span class="text-red-500 font-bold text-base">✗</span> {{ session('error') }}
        </div>
    @endif

    @yield('content')
</main>

@livewireScripts
@stack('scripts')
</body>
</html>
