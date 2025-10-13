@extends('layouts.app')

@section('content')
<div class="py-12 animate-fadeIn">
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-gray-900 dark:text-gray-100">
            <p class="text-lg font-medium">
                Estás autenticado como <strong>{{ Auth::user()->role }}</strong>.
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Escolhe uma opção para começar:
            </p>

            <div class="mt-6 space-y-3">
                <a href="{{ route('rooms.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 btn-animated"
                   aria-label="Ver salas de chat">
                    <span>Ver Salas</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                <a href="{{ route('dm.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-300 text-gray-800 rounded-full hover:bg-gray-400 btn-animated"
                   aria-label="Abrir mensagens diretas">
                    <span>Mensagens Diretas</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 8h2a2 2 0 012 2v8a2 2 0 01-2 2h-2M7 8H5a2 2 0 00-2 2v8a2 2 0 002 2h2m10-12V6a4 4 0 00-8 0v2m8 0H7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
