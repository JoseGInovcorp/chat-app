<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Título dinâmico --}}
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    {{-- Meta tags para SEO --}}
    <meta name="description" content="@yield('meta_description', 'Aplicação de chat Laravel')">
    <meta name="author" content="InovCorp">

    {{-- CSS via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Stack opcional para scripts no <head> --}}
    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900"
      @auth data-auth-id="{{ auth()->id() }}" @endauth>
    <div class="min-h-screen flex">
        @auth
            @includeIf('layouts.navigation') {{-- Sidebar condicional --}}
        @endauth

        <div class="flex-1 flex flex-col">
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow" role="banner">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1" role="main">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>

    {{-- Stack para scripts no fim do body --}}
    @stack('scripts')
</body>
</html>
