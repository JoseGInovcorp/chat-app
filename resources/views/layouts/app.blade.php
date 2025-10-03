<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- CSS --}}
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex bg-gray-100 dark:bg-gray-900">
        @auth
            @include('layouts.navigation') <!-- Sidebar -->
        @endauth

        <div class="flex-1 flex flex-col">
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1">
                {{-- Suporta tanto @section('content') como <x-app-layout> --}}
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        </div>
    </div>

    {{-- JS no fim do body para garantir que Echo já existe --}}
    @vite(['resources/js/app.js'])

    {{-- Scripts adicionais injetados pelas views --}}
    @stack('scripts')
</body>
</html>
