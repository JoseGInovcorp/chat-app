@extends('layouts.app')

@section('content')
<div id="dm-app"
     data-peer-id="{{ $user->id }}"
     data-auth-id="{{ auth()->id() }}"
     class="h-[calc(100vh-8rem)] flex flex-col">

    <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-white dark:bg-gray-900" id="dm-window">
        @foreach($messages as $m)
            <div class="flex flex-col {{ $m->sender_id === auth()->id() ? 'items-end' : 'items-start' }}">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {{ $m->sender->name }}
                </div>
                <div class="max-w-xs px-3 py-2 rounded-lg
                    {{ $m->sender_id === auth()->id()
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                    <p class="text-sm">{{ $m->body }}</p>
                    <span class="text-[10px] opacity-70">
                        {{ $m->created_at->format('H:i') }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <form id="dm-form"
          class="p-4 border-t bg-gray-50 dark:bg-gray-800 flex gap-2">
        @csrf
        <input type="text" id="dm-input"
               class="flex-1 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
               placeholder="Escreve uma mensagem..." required>
        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Enviar
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('dm-app');
    const peerId = app.dataset.peerId;
    const authId = app.dataset.authId;
    const win = document.getElementById('dm-window');
    const form = document.getElementById('dm-form');
    const input = document.getElementById('dm-input');

    const appendMessage = (msg) => {
        const div = document.createElement('div');
        div.className = "flex flex-col " + (parseInt(msg.sender_id) === parseInt(authId) ? 'items-end' : 'items-start');
        div.innerHTML = `
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                ${msg.sender_name ?? ''}
            </div>
            <div class="max-w-xs px-3 py-2 rounded-lg ${
                parseInt(msg.sender_id) === parseInt(authId)
                    ? 'bg-blue-500 text-white'
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
            }">
                <p class="text-sm">${msg.body}</p>
                <span class="text-[10px] opacity-70">${msg.created_at}</span>
            </div>
        `;
        win.appendChild(div);
        win.scrollTop = win.scrollHeight;
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        const res = await fetch(`/dm/${peerId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ body })
        });
        const msg = await res.json();
        appendMessage(msg);
        input.value = '';
    });

    Echo.private(`dm.${authId}`)
        .listen('DirectMessageSent', (e) => {
            const isActiveThread = (parseInt(e.sender_id) === parseInt(peerId)) ||
                                   (parseInt(e.recipient_id) === parseInt(peerId));
            if (isActiveThread) appendMessage(e);
        });
});
</script>
@endpush
