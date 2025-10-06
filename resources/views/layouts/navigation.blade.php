<aside class="w-64 bg-white dark:bg-gray-800 border-r h-screen flex flex-col">
    <!-- Logo -->
    <div class="p-4 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <x-application-logo class="h-8 w-auto text-gray-800 dark:text-gray-200" />
            <span class="font-bold text-gray-700 dark:text-gray-200">Chat App</span>
        </a>
    </div>

    <!-- Conteúdo da Sidebar -->
    <div class="flex-1 overflow-y-auto p-4 space-y-6">
        <!-- Salas -->
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2 flex items-center gap-1">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M3 7h18M3 12h18M3 17h18"/></svg>
                Salas
            </h4>
            <ul class="space-y-1">
                @forelse($rooms as $room)
                    <li>
                        <a href="{{ route('rooms.show', $room) }}"
                           class="room-link flex items-center justify-between px-3 py-2 rounded-md transition hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->is('rooms/'.$room->slug) ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                           data-room-id-link="{{ $room->id }}">
                            <div class="flex items-center gap-3">
                                <img src="{{ $room->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($room->name) }}"
                                     class="w-7 h-7 rounded border border-gray-300 dark:border-gray-600 shadow-sm" alt="">
                                <span class="text-sm text-gray-700 dark:text-gray-200 font-medium">{{ $room->name }}</span>
                            </div>
                            <span class="room-unread w-2 h-2 rounded-full bg-red-500 {{ $room->unread_count > 0 ? 'animate-ping' : 'hidden' }}"
                                    data-badge-type="room"
                                    data-badge-id="{{ $room->id }}"></span>
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-gray-400">Sem salas</li>
                @endforelse
            </ul>
        </div>

        <!-- Diretas -->
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2 flex items-center gap-1">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M17 8h2a2 2 0 012 2v8a2 2 0 01-2 2h-2M7 8H5a2 2 0 00-2 2v8a2 2 0 002 2h2m10-12V6a4 4 0 00-8 0v2m8 0H7"/></svg>
                Diretas
            </h4>
            <ul class="space-y-1">
                @forelse($directContacts as $contact)
                    <li>
                        <a href="{{ route('dm.show', $contact) }}"
                           data-user-id="{{ $contact->id }}"
                           class="direct-contact flex items-center justify-between px-3 py-2 rounded-md transition hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->is('dm/'.$contact->id) ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                            <div class="flex items-center gap-3">
                                <img src="{{ $contact->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($contact->name) }}"
                                     class="w-7 h-7 rounded-full border border-gray-300 dark:border-gray-600 shadow-sm" alt="">
                                <span class="text-sm text-gray-700 dark:text-gray-200 font-medium">{{ $contact->name }}</span>
                            </div>
                            <span class="contact-unread w-2 h-2 rounded-full bg-red-500 {{ $contact->unread_count > 0 ? 'animate-ping' : 'hidden' }}"
                                    data-badge-type="dm"
                                    data-badge-id="{{ $contact->id }}"></span>
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-gray-400">Sem diretas</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Perfil / Logout -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <img src="{{ Auth::user()->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name) }}"
                 class="w-9 h-9 rounded-full border border-gray-300 dark:border-gray-600 shadow-sm" alt="">
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ Auth::user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="text-xs text-red-500 hover:underline flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                             d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1"/></svg>
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- Script: expõe auth/room e aplica/limpa badges pendentes -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Expor auth e sala atual
    document.body.dataset.authId = "{{ auth()->id() }}";
    @if(request()->routeIs('rooms.show') && isset($room))
        document.body.dataset.roomId = "{{ $room->id }}";
    @else
        delete document.body.dataset.roomId;
    @endif

    // --- DMs ---
    window.applyPendingBadge = function(senderId) {
        if (!senderId) return;
        senderId = String(senderId);

        // Atualiza DOM imediatamente
        const badge = document.querySelector(
            `.contact-unread[data-badge-type="dm"][data-badge-id="${senderId}"]`
        );
        if (badge) badge.classList.remove('hidden');

        // Guarda em localStorage
        const pending = JSON.parse(localStorage.getItem("pendingBadges") || "[]").map(String);
        if (!pending.includes(senderId)) {
            pending.push(senderId);
            localStorage.setItem("pendingBadges", JSON.stringify(pending));
        }
    };

    window.clearPendingBadge = function(senderId) {
        if (!senderId) return;
        senderId = String(senderId);

        // Atualiza DOM
        const badge = document.querySelector(
            `.contact-unread[data-badge-type="dm"][data-badge-id="${senderId}"]`
        );
        if (badge) badge.classList.add('hidden');

        // Atualiza localStorage
        const filtered = JSON.parse(localStorage.getItem("pendingBadges") || "[]")
            .map(String)
            .filter(x => x !== senderId);
        localStorage.setItem("pendingBadges", JSON.stringify(filtered));
    };

    // --- Salas ---
    window.applyPendingRoomBadge = function(roomId) {
        if (!roomId) return;
        roomId = String(roomId);

        // Atualiza DOM imediatamente
        const badge = document.querySelector(
            `.room-unread[data-badge-type="room"][data-badge-id="${roomId}"]`
        );
        if (badge) badge.classList.remove('hidden');

        // Guarda em localStorage
        const pending = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]").map(String);
        if (!pending.includes(roomId)) {
            pending.push(roomId);
            localStorage.setItem("pendingRoomBadges", JSON.stringify(pending));
        }
    };

    window.clearPendingRoomBadge = function(roomId) {
        if (!roomId) return;
        roomId = String(roomId);

        // Atualiza DOM
        const badge = document.querySelector(
            `.room-unread[data-badge-type="room"][data-badge-id="${roomId}"]`
        );
        if (badge) badge.classList.add('hidden');

        // Atualiza localStorage
        const filtered = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]")
            .map(String)
            .filter(x => x !== roomId);
        localStorage.setItem("pendingRoomBadges", JSON.stringify(filtered));
    };

    // --- Reaplicar badges guardados no arranque ---
    (function applyAll() {
        const pendingDMs = JSON.parse(localStorage.getItem("pendingBadges") || "[]").map(String);
        pendingDMs.forEach(id => {
            const badge = document.querySelector(
                `.contact-unread[data-badge-type="dm"][data-badge-id="${id}"]`
            );
            if (badge) badge.classList.remove('hidden');
        });

        const pendingRooms = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]").map(String);
        pendingRooms.forEach(rid => {
            const rb = document.querySelector(
                `.room-unread[data-badge-type="room"][data-badge-id="${rid}"]`
            );
            if (rb) rb.classList.remove('hidden');
        });
    })();

    // --- Cross-tab sync ---
    window.addEventListener('storage', (e) => {
        if (e.key === 'pendingBadges') {
            const pendingDMs = JSON.parse(localStorage.getItem("pendingBadges") || "[]").map(String);
            pendingDMs.forEach(id => {
                const badge = document.querySelector(
                    `.contact-unread[data-badge-type="dm"][data-badge-id="${id}"]`
                );
                if (badge) badge.classList.remove('hidden');
            });
        }
        if (e.key === 'pendingRoomBadges') {
            const pendingRooms = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]").map(String);
            pendingRooms.forEach(rid => {
                const rb = document.querySelector(
                    `.room-unread[data-badge-type="room"][data-badge-id="${rid}"]`
                );
                if (rb) rb.classList.remove('hidden');
            });
        }
    });

    // --- Clicks limpam badges ---
    document.querySelectorAll('.direct-contact').forEach(link => {
        link.addEventListener('click', () => {
            const id = link.dataset.userId;
            if (id) window.clearPendingBadge(id);
        });
    });
    document.querySelectorAll('.room-link').forEach(link => {
        link.addEventListener('click', () => {
            const id = link.dataset.roomIdLink;
            if (id) window.clearPendingRoomBadge(id);
        });
    });

    // --- NOVO: ouvir eventos vindos do bootstrap (Echo listeners) ---
    window.addEventListener("pendingBadges:updated", (ev) => {
        const id = ev?.detail?.sender_id;
        if (id) window.applyPendingBadge(String(id));
    });

    window.addEventListener("pendingRoomBadges:updated", (ev) => {
        const rid = ev?.detail?.room_id;
        if (rid) window.applyPendingRoomBadge(String(rid));
    });
});
</script>

</aside>
