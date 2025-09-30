@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 animate-fadeIn">
    <div class="text-center space-y-6">
        <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-200">💬 Chat App</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">
            Sistema de comunicação interna para equipas — rápido, privado e em tempo real.
        </p>
        <div class="space-x-4">
            <a href="{{ route('login') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 btn-animated">
                Entrar
            </a>
            <a href="{{ route('register') }}"
               class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 btn-animated">
                Criar Conta
            </a>
        </div>
        <p class="text-sm text-gray-500 mt-4">
            Desenvolvido por José G. durante estágio na InovCorp.
        </p>
    </div>
</div>
@endsection
