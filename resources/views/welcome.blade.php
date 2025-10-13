@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 animate-fadeIn">
    <div class="text-center space-y-6 max-w-md">
        <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-200">💬 Chat App</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">
            Sistema de comunicação interna para equipas — rápido, privado e em tempo real.
        </p>
        <div class="flex justify-center gap-4">
            <a href="{{ route('login') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 btn-animated"
               aria-label="Entrar na aplicação">
                <span>Entrar</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12H3m6 6l-6-6 6-6"/>
                </svg>
            </a>
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-300 text-gray-800 rounded-full hover:bg-gray-400 btn-animated"
               aria-label="Criar nova conta">
                <span>Criar Conta</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 4v16m8-8H4"/>
                </svg>
            </a>
        </div>
        <p class="text-sm text-gray-500 mt-4">
            Desenvolvido por José G. durante estágio na InovCorp.
        </p>
    </div>
</div>
@endsection
