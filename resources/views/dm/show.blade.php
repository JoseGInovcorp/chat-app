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

    <div id="dm-window" class="flex-1 overflow-y-auto px-6 py-4 space-y-4 bg-white dark:bg-gray-900">
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
               placeholder="Escreve uma mensagem..." required>
        <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 btn-animated flex items-center gap-2">
            <span>Enviar</span> <span>📤</span>
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('dm-app');
    const peerId = parseInt(app.dataset.peerId, 10);
    const authId = parseInt(app.dataset.authId, 10);
    const win = document.getElementById('dm-window');
    const form = document.getElementById('dm-form');
    const input = document.getElementById('dm-input');

    // Scroll inicial
    win.scrollTop = win.scrollHeight;

    let lastSenderId = (() => {
        const lastMsg = win.querySelector('[data-message-id]:last-child');
        if (!lastMsg) return null;
        const nameEl = lastMsg.querySelector('.text-xs');
        return nameEl ? nameEl.textContent.trim() : null;
    })();

    const appendMessage = (msg) => {
        try {
            // Normaliza payload (se vier aninhado)
            const m = msg?.message ?? msg;
            if (!m || (!m.id && !m.temp_id)) return;

            const messageKey = m.id ? `message-${m.id}` : `temp-${m.temp_id}`;
            // Evitar duplicados
            if (win.querySelector(`[data-message-id="${messageKey}"]`)) return;

            const isOwn = parseInt(m.sender_id, 10) === authId;
            const isSameSender = lastSenderId === String(m.sender_id);

            const div = document.createElement('div');
            div.setAttribute('data-message-id', messageKey);
            div.className = `flex flex-col ${isOwn ? 'items-end' : 'items-start'} animate-fadeInUp`;

            div.innerHTML = `
                ${!isSameSender ? `
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1 font-semibold">
                    ${m.sender_name ?? ''}
                </div>` : ''}
                <div class="max-w-xs px-4 py-2 rounded-xl shadow-sm font-medium ${
                    isOwn
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
                }">
                    <p class="text-sm whitespace-pre-line">${m.body ?? ''}</p>
                    <span class="text-[10px] opacity-70 block text-right mt-1">${m.created_at ?? ''}</span>
                </div>
            `;

            win.appendChild(div);
            win.scrollTop = win.scrollHeight;
            lastSenderId = String(m.sender_id ?? lastSenderId);

            // Limpar badge DM se esta thread estiver aberta
            if (typeof window.clearPendingBadge === 'function') {
                window.clearPendingBadge(peerId);
            }
        } catch (err) {
            console.warn('appendMessage error (dm)', err);
        }
    };

    // Expõe globalmente para o bootstrap.js
    window.appendMessage = appendMessage;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        // cria temp_id e elemento temporário visível
        const tempId = `t${Date.now()}`;
        appendMessage({ temp_id: tempId, sender_id: authId, recipient_id: peerId, body, sender_name: 'Tu', created_at: 'Agora' });

        try {
            const res = await fetch(`/dm/${peerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ body, temp_id: tempId }),
            });

            if (!res.ok) {
                console.error('Falha ao enviar DM:', res.status);
                return;
            }

            const msg = await res.json();

            // Substitui elemento temp pelo real ou faz append deduplicado
            const tempEl = win.querySelector(`[data-message-id="temp-${tempId}"]`);
            if (tempEl) {
                // cria markup real (reusa appendMessage para normalizar)
                // remove temp antes de inserir real para evitar duplicados visuais
                tempEl.remove();
            }
            appendMessage(msg);
            input.value = '';
        } catch (err) {
            console.error('Erro de rede ao enviar DM:', err);
        }
    });


    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

    // NOTA: o listener Echo para DMs foi removido daqui.
    // O bootstrap.js central já trata eventos e chama window.appendMessage quando a thread está aberta.
});
</script>

@endpush
