@extends('layouts.app')

@section('content')
<div id="room-app"
     data-room-id="{{ $room->id }}"
     class="h-[calc(100vh-8rem)] flex flex-col animate-fadeIn">

    <div class="flex items-center justify-between px-4 py-2 bg-white dark:bg-gray-800 shadow">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $room->name }}
        </h2>

        @can('invite', $room)
            <a href="{{ route('rooms.invite', $room) }}"
               class="text-sm text-blue-600 dark:text-blue-400 hover:underline link-hover">
                + Convidar
            </a>
        @endcan
    </div>

    <div id="messages"
         class="flex-1 overflow-y-auto p-4 space-y-4 bg-white dark:bg-gray-900">
        @php $lastSenderId = null; @endphp
        @foreach($messages as $message)
            @php $isSameSender = $lastSenderId === $message->sender_id; @endphp
            <div id="message-{{ $message->id }}"
                 class="flex flex-col {{ $message->sender_id === auth()->id() ? 'items-end' : 'items-start' }} animate-fadeInUp">
                @unless($isSameSender)
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {{ $message->sender->name }}
                    </div>
                @endunless
                <div class="max-w-xs px-3 py-2 rounded-lg
                    {{ $message->sender_id === auth()->id()
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                    <p class="text-sm">{{ $message->body }}</p>
                    <span class="text-[10px] opacity-70">
                        {{ $message->created_at->format('H:i') }}
                    </span>
                </div>
                @can('delete', $message)
                    <button data-id="{{ $message->id }}"
                            class="delete-message text-xs text-red-500 hover:underline mt-1">
                        Apagar
                    </button>
                @endcan
            </div>
            @php $lastSenderId = $message->sender_id; @endphp
        @endforeach
    </div>

    <form id="message-form" action="{{ route('messages.store') }}" method="POST"
          class="p-4 border-t bg-gray-50 dark:bg-gray-800 flex gap-2">
        @csrf
        <input type="hidden" name="room_id" value="{{ $room->id }}">

        <textarea name="body" id="message-input" rows="2"
                  class="flex-1 resize-none rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                  placeholder="Escreve uma mensagem..." required></textarea>

        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 btn-animated">
            Enviar
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // expõe ids globais
    window.roomId = {{ $room->id }};
    window.userId = {{ auth()->id() }};

    const messagesDiv = document.getElementById('messages');
    const form = document.getElementById('message-form');
    const input = document.getElementById('message-input');

    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    // Inicializa com o último sender visível no DOM
    let lastSenderId = (() => {
        const lastMsg = messagesDiv.querySelector('[id^="message-"]:last-child');
        if (!lastMsg) return null;
        const nameEl = lastMsg.querySelector('.text-xs');
        return nameEl ? nameEl.textContent.trim() : null;
    })();

    window.appendMessage = (msg) => {
        const isOwn = parseInt(msg.sender_id) === parseInt(window.userId);
        const isSameSender = lastSenderId === msg.sender_id;

        const div = document.createElement('div');
        div.id = `message-${msg.id}`;
        div.className = `flex flex-col ${isOwn ? 'items-end' : 'items-start'} animate-fadeInUp`;

        div.innerHTML = `
            ${!isSameSender ? `
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                ${msg.sender_name}
            </div>` : ''}
            <div class="max-w-xs px-3 py-2 rounded-lg ${
                isOwn
                    ? 'bg-blue-500 text-white'
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
            }">
                <p class="text-sm">${msg.body}</p>
                <span class="text-[10px] opacity-70">${msg.created_at}</span>
            </div>
        `;

        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
        lastSenderId = msg.sender_id;
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ body, room_id: window.roomId })
        });

        if (response.ok) {
            const data = await response.json();
            window.appendMessage(data);
            input.value = '';
        }
    });

    // 🔑 Novo: Enter envia, Shift+Enter quebra linha
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

    messagesDiv.addEventListener('click', async (e) => {
        if (e.target.classList.contains('delete-message')) {
            const id = e.target.dataset.id;
            const response = await fetch(`/messages/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });
            if (response.ok) {
                document.getElementById(`message-${id}`).remove();
            }
        }
    });

    // ------------- badge sync: limpa pendingRoomBadges e oculta badge da sidebar -------------
    document.addEventListener('DOMContentLoaded', () => {
        try {
            const pendingRooms = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]");
            const filtered = pendingRooms.filter(x => parseInt(x) !== parseInt(window.roomId));
            localStorage.setItem("pendingRoomBadges", JSON.stringify(filtered));

            const badge = document.querySelector(`.room-unread[data-room-id="${window.roomId}"]`);
            if (badge) badge.classList.add('hidden');

            if (typeof window.clearPendingRoomBadge === 'function') {
                window.clearPendingRoomBadge(window.roomId);
            }
        } catch (err) {
            console.warn('Erro ao limpar pendingRoomBadges:', err);
        }
    });
</script>
@endpush
