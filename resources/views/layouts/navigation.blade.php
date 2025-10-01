<aside class="w-64 bg-white dark:bg-gray-800 border-r h-screen flex flex-col">
    <!-- Logo -->
    <div class="p-4 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <x-application-logo class="h-8 w-auto text-gray-800 dark:text-gray-200" />
            <span class="font-bold text-gray-700 dark:text-gray-200">Chat App</span>
        </a>
    </div>

    <!-- Conteúdo da Sidebar -->
    <div class="flex-1 overflow-y-auto p-4">
        <!-- Salas -->
        <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Salas</h4>
        <ul class="space-y-1">
            @forelse($rooms as $room)
                <li>
                    <a href="{{ route('rooms.show', $room) }}"
                       class="room-link flex items-center justify-between px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->is('rooms/'.$room->slug) ? 'bg-gray-100 dark:bg-gray-700' : '' }}"
                       data-room-id-link="{{ $room->id }}">
                        <div class="flex items-center gap-2">
                            <img src="{{ $room->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($room->name) }}"
                                 class="w-6 h-6 rounded" alt="">
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $room->name }}</span>
                        </div>
                        @if($room->unread_count > 0)
                            <span class="inline-block w-2 h-2 bg-red-500 rounded-full animate-pulse room-unread" data-room-id="{{ $room->id }}"></span>
                        @else
                            <span class="inline-block w-2 h-2 bg-red-500 rounded-full hidden room-unread" data-room-id="{{ $room->id }}"></span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="text-sm text-gray-400">Sem salas</li>
            @endforelse
        </ul>

        <!-- Diretas -->
        <h4 class="text-xs font-semibold text-gray-500 uppercase mt-6 mb-2">Diretas</h4>
        <ul class="space-y-1">
            @forelse($directContacts as $contact)
                <li>
                    <a href="{{ route('dm.show', $contact) }}"
                       data-user-id="{{ $contact->id }}"
                       class="direct-contact flex items-center justify-between px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->is('dm/'.$contact->id) ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                        <div class="flex items-center gap-2">
                            <img src="{{ $contact->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($contact->name) }}"
                                 class="w-6 h-6 rounded-full" alt="">
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $contact->name }}</span>
                        </div>
                        @if($contact->unread_count > 0)
                            <span class="inline-block w-2 h-2 bg-red-500 rounded-full animate-pulse contact-unread" data-user-id-badge="{{ $contact->id }}"></span>
                        @else
                            <span class="inline-block w-2 h-2 bg-red-500 rounded-full hidden contact-unread" data-user-id-badge="{{ $contact->id }}"></span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="text-sm text-gray-400">Sem diretas</li>
            @endforelse
        </ul>
    </div>

    <!-- Perfil / Logout -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2">
            <img src="{{ Auth::user()->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name) }}"
                 class="w-8 h-8 rounded-full" alt="">
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-red-500 hover:underline">Sair</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Script: expõe auth/room e aplica/limpa badges pendentes -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    document.body.dataset.authId = "{{ auth()->id() }}";
    @if(request()->routeIs('rooms.show') && isset($room))
        document.body.dataset.roomId = "{{ $room->id }}";
    @else
        delete document.body.dataset.roomId;
    @endif

    // simple, deterministic helpers
    window.applyPendingBadge = window.applyPendingBadge || function(senderId) {
        if (!senderId) return;
        senderId = String(senderId);
        const badge = document.querySelector(`.contact-unread[data-user-id-badge="${senderId}"]`);
        if (badge) badge.classList.remove('hidden');
        const pending = JSON.parse(localStorage.getItem("pendingBadges") || "[]").map(String);
        if (!pending.includes(senderId)) {
            pending.push(senderId);
            localStorage.setItem("pendingBadges", JSON.stringify(pending));
        }
    };

    window.clearPendingBadge = window.clearPendingBadge || function(senderId) {
        if (!senderId) return;
        senderId = String(senderId);
        const filtered = JSON.parse(localStorage.getItem("pendingBadges") || "[]").map(String).filter(x => x !== senderId);
        localStorage.setItem("pendingBadges", JSON.stringify(filtered));
        const badge = document.querySelector(`.contact-unread[data-user-id-badge="${senderId}"]`);
        if (badge) badge.classList.add('hidden');
    };

    window.applyPendingRoomBadge = window.applyPendingRoomBadge || function(roomId) {
        if (!roomId) return;
        roomId = String(roomId);
        const badge = document.querySelector(`.room-unread[data-room-id="${roomId}"]`);
        if (badge) {
            badge.classList.remove('hidden');
            const remaining = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]").map(String).filter(x => x !== roomId);
            localStorage.setItem("pendingRoomBadges", JSON.stringify(remaining));
            return;
        }
        const pending = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]").map(String);
        if (!pending.includes(roomId)) {
            pending.push(roomId);
            localStorage.setItem("pendingRoomBadges", JSON.stringify(pending));
        }
    };

    window.clearPendingRoomBadge = window.clearPendingRoomBadge || function(roomId) {
        if (!roomId) return;
        roomId = String(roomId);
        const remaining = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]").map(String).filter(x => x !== roomId);
        localStorage.setItem("pendingRoomBadges", JSON.stringify(remaining));
        const badge = document.querySelector(`.room-unread[data-room-id="${roomId}"]`);
        if (badge) badge.classList.add('hidden');
    };

    // apply any stored pendings immediately
    (function applyAll() {
        const pendingDMs = JSON.parse(localStorage.getItem("pendingBadges") || "[]").map(String);
        pendingDMs.forEach(id => {
            const badge = document.querySelector(`.contact-unread[data-user-id-badge="${id}"]`);
            if (badge) badge.classList.remove('hidden');
        });
        const pendingRooms = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]").map(String);
        pendingRooms.forEach(rid => {
            const rb = document.querySelector(`.room-unread[data-room-id="${rid}"]`);
            if (rb) rb.classList.remove('hidden');
        });
    })();

    // listen for bootstrap dispatches
    window.addEventListener('pendingBadges:updated', (e) => {
        const sid = e?.detail?.sender_id;
        if (sid) window.applyPendingBadge(sid);
    });
    window.addEventListener('pendingRoomBadges:updated', (e) => {
        const rid = e?.detail?.room_id;
        if (rid) window.applyPendingRoomBadge(rid);
    });

    // cross-tab storage
    window.addEventListener('storage', (e) => {
        if (e.key === 'pendingBadges') {
            const pendingDMs = JSON.parse(localStorage.getItem("pendingBadges") || "[]").map(String);
            pendingDMs.forEach(id => {
                const badge = document.querySelector(`.contact-unread[data-user-id-badge="${id}"]`);
                if (badge) badge.classList.remove('hidden');
            });
        }
        if (e.key === 'pendingRoomBadges') {
            const pendingRooms = JSON.parse(localStorage.getItem("pendingRoomBadges") || "[]").map(String);
            pendingRooms.forEach(rid => {
                const rb = document.querySelector(`.room-unread[data-room-id="${rid}"]`);
                if (rb) rb.classList.remove('hidden');
            });
        }
    });

    // click handlers to clear pendings
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
});
</script>
</aside>
