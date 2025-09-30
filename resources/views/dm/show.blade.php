@extends('layouts.app')

@section('content')
<div id="dm-app"
     data-peer-id="{{ $user->id }}"
     data-auth-id="{{ auth()->id() }}"
     class="h-[calc(100vh-8rem)] flex flex-col animate-fadeIn">

    <div class="flex items-center justify-between px-4 py-2 bg-white dark:bg-gray-800 shadow">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Conversa com {{ $user->name }}
        </h2>
    </div>

    <div id="dm-window" class="flex-1 overflow-y-auto p-4 space-y-4 bg-white dark:bg-gray-900">
        @php $lastSenderId = null; @endphp
        @foreach($messages as $m)
            @php $isSameSender = $lastSenderId === $m->sender_id; @endphp
            <div class="flex flex-col {{ $m->sender_id === auth()->id() ? 'items-end' : 'items-start' }} animate-fadeInUp">
                @unless($isSameSender)
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {{ $m->sender->name }}
                    </div>
                @endunless
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
            @php $lastSenderId = $m->sender_id; @endphp
        @endforeach
    </div>

    <form id="dm-form" class="p-4 border-t bg-gray-50 dark:bg-gray-800 flex gap-2">
        @csrf
        <input type="text" id="dm-input"
               class="flex-1 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
               placeholder="Escreve uma mensagem..." required>
        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 btn-animated">
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

    win.scrollTop = win.scrollHeight;

    // Inicializa com o último sender visível no DOM
    let lastSenderId = (() => {
        const lastMsg = win.querySelector('[id^="message-"]:last-child');
        if (!lastMsg) return null;
        const nameEl = lastMsg.querySelector('.text-xs');
        return nameEl ? nameEl.textContent.trim() : null;
    })();

    const appendMessage = (msg) => {
        const isOwn = parseInt(msg.sender_id) === parseInt(authId);
        const isSameSender = lastSenderId === msg.sender_id;

        const div = document.createElement('div');
        div.className = `flex flex-col ${isOwn ? 'items-end' : 'items-start'} animate-fadeInUp`;

        div.innerHTML = `
            ${!isSameSender ? `
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                ${msg.sender_name ?? ''}
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

        win.appendChild(div);
        win.scrollTop = win.scrollHeight;
        lastSenderId = msg.sender_id;
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        try {
            const res = await fetch(`/dm/${peerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ body }),
            });

            if (!res.ok) {
                console.error('Falha ao enviar DM:', res.status);
                return;
            }

            const msg = await res.json();
            appendMessage(msg);
            input.value = '';
        } catch (err) {
            console.error('Erro de rede ao enviar DM:', err);
        }
    });

    Echo.private(`dm.${authId}`)
        .listen('DirectMessageSent', (e) => {
            if (parseInt(e.sender_id) === parseInt(authId)) {
                console.log("🛑 Ignorado: DM do próprio utilizador");
                return;
            }

            const isActiveThread =
                (parseInt(e.sender_id) === parseInt(peerId)) ||
                (parseInt(e.recipient_id) === parseInt(peerId));

            if (isActiveThread) appendMessage(e);
        });
});
</script>
@endpush
