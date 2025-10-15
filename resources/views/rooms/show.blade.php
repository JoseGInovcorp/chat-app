@extends('layouts.app')

@section('content')
<div id="room-app"
     data-room-id="{{ $room->id }}"
     data-room-slug="{{ $room->slug }}"
     class="h-[calc(100vh-8rem)] flex flex-col animate-fadeIn">

    <div class="flex items-center justify-between px-6 py-3 bg-white dark:bg-gray-800 shadow-sm border-b">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
            {{ $room->name }}
        </h2>

        {{-- Botão de convite só para admin --}}
        @can('invite', $room)
            <a href="{{ route('rooms.invite', $room) }}"
               class="inline-flex items-center gap-1 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <span>+ Convidar</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M12 4v16m8-8H4"/></svg>
            </a>
        @endcan
    </div>

    <div id="messages"
         class="flex-1 overflow-y-auto px-6 py-4 space-y-4 bg-white dark:bg-gray-900">
        @php $lastSenderId = null; @endphp
        @foreach($messages as $message)
            @php $isSameSender = $lastSenderId === $message->sender_id; @endphp
            <div id="message-{{ $message->id }}"
                 class="flex flex-col {{ $message->sender_id === auth()->id() ? 'items-end' : 'items-start' }} animate-fadeInUp">
                @unless($isSameSender)
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-semibold">
                        {{ optional($message->sender)->name ?? 'Utilizador removido' }}
                    </div>
                @endunless
                <div class="max-w-xs px-4 py-2 rounded-xl shadow-sm font-medium
                    {{ $message->sender_id === auth()->id()
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                    <p class="text-sm whitespace-pre-line">{{ $message->body }}</p>
                    <span class="text-[10px] opacity-70 block text-right mt-1">
                        {{ $message->created_at->format('H:i') }}
                    </span>
                </div>
                @can('delete', $message)
                    <button data-id="{{ $message->id }}"
                            class="delete-message text-xs text-red-500 hover:underline mt-1" aria-label="Apagar mensagem">
                        Apagar
                    </button>
                @endcan
            </div>
            @php $lastSenderId = $message->sender_id; @endphp
        @endforeach
    </div>

    <form id="message-form" action="{{ route('messages.store') }}" method="POST"
          class="px-6 py-4 border-t bg-gray-50 dark:bg-gray-800 flex gap-2">
        @csrf
        <input type="hidden" name="room_id" value="{{ $room->id }}">

        <textarea name="body" id="message-input" rows="2"
                  class="flex-1 resize-none rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500"
                  placeholder="Escreve uma mensagem..." required></textarea>

        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 btn-animated flex items-center gap-2" aria-label="Enviar mensagem">
            <span>Enviar</span> <span>📤</span>
        </button>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/room.js')
@endpush
