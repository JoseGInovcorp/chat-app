@extends('layouts.app')

@section('content')
<div id="room-app"
     data-room-id="{{ $room->id }}"
     data-auth-id="{{ auth()->id() }}"
     class="h-[calc(100vh-8rem)] flex flex-col">

    <div class="flex items-center justify-between px-4 py-2 bg-white dark:bg-gray-800 shadow">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $room->name }}
        </h2>

        @can('invite', $room)
            <a href="{{ route('rooms.invite', $room) }}"
               class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                + Convidar
            </a>
        @endcan
    </div>

    <div id="messages"
         class="flex-1 overflow-y-auto p-4 space-y-4 bg-white dark:bg-gray-900">
        @foreach($messages as $message)
            <div id="message-{{ $message->id }}"
                 class="flex flex-col {{ $message->sender_id === auth()->id() ? 'items-end' : 'items-start' }}">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ $message->sender->name }}
                </div>
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
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Enviar
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const messagesDiv = document.getElementById('messages');
    const form = document.getElementById('message-form');
    const input = document.getElementById('message-input');
    const roomId = document.getElementById('room-app').dataset.roomId;
    const authId = document.getElementById('room-app').dataset.authId;

    messagesDiv.scrollTop = messagesDiv.scrollHeight;

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
            body: JSON.stringify({ body, room_id: roomId })
        });

        if (response.ok) {
            const data = await response.json();
            const div = document.createElement('div');
            div.id = `message-${data.id}`;
            div.className = "flex flex-col items-end";
            div.innerHTML = `
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    ${data.sender_name}
                </div>
                <div class="max-w-xs px-3 py-2 rounded-lg bg-blue-500 text-white">
                    <p class="text-sm">${data.body}</p>
                    <span class="text-[10px] opacity-70">${data.created_at}</span>
                </div>
                <button data-id="${data.id}" class="delete-message text-xs text-red-500 hover:underline mt-1">Apagar</button>
            `;
            messagesDiv.appendChild(div);
            input.value = '';
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
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

    Echo.private(`room.${roomId}`)
        .listen('RoomMessageSent', (e) => {
            const div = document.createElement('div');
            div.id = `message-${e.id}`;
            div.className = "flex flex-col " + (parseInt(e.sender_id) === parseInt(authId) ? 'items-end' : 'items-start');
            div.innerHTML = `
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    ${e.sender_name}
                </div>
                <div class="max-w-xs px-3 py-2 rounded-lg ${
                    parseInt(e.sender_id) === parseInt(authId)
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
                }">
                    <p class="text-sm">${e.body}</p>
                    <span class="text-[10px] opacity-70">${e.created_at}</span>
                </div>
            `;
            messagesDiv.appendChild(div);
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        });
</script>
@endpush
