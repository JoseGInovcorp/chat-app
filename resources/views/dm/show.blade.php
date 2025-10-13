@extends('layouts.app')

@section('content')
<div id="dm-app"
     data-peer-id="{{ $user->id }}"
     data-auth-id="{{ auth()->id() }}"
     class="h-[calc(100vh-8rem)] flex flex-col animate-fadeIn">

    <div class="flex items-center justify-between px-6 py-3 bg-white dark:bg-gray-800 shadow-sm border-b">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
            Conversa com {{ $user->name }}
        </h2>
    </div>

    <div id="dm-window" class="flex-1 overflow-y-auto px-6 py-4 space-y-4 bg-white dark:bg-gray-900" role="log" aria-live="polite">
        @php $lastSenderId = null; @endphp
        @foreach($messages as $m)
            @php $isSameSender = $lastSenderId === $m->sender_id; @endphp
            <div class="flex flex-col {{ $m->sender_id === auth()->id() ? 'items-end' : 'items-start' }} animate-fadeInUp">
                @unless($isSameSender)
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-semibold">
                        {{ $m->sender->name }}
                    </div>
                @endunless
                <div class="max-w-xs px-4 py-2 rounded-xl shadow-sm font-medium
                    {{ $m->sender_id === auth()->id()
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                    <p class="text-sm whitespace-pre-line">{{ $m->body }}</p>
                    <span class="text-[10px] opacity-70 block text-right mt-1">
                        {{ $m->created_at->format('H:i') }}
                    </span>
                </div>
            </div>
            @php $lastSenderId = $m->sender_id; @endphp
        @endforeach
    </div>

    <form id="dm-form" class="px-6 py-4 border-t bg-gray-50 dark:bg-gray-800 flex gap-2">
        @csrf
        <input type="text" id="dm-input"
               class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
               placeholder="Escreve uma mensagem..." required aria-label="Escrever mensagem direta">
        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 btn-animated flex items-center gap-2"
                aria-label="Enviar mensagem direta">
            <span>Enviar</span> <span aria-hidden="true">📤</span>
        </button>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/dm.js')
@endpush
