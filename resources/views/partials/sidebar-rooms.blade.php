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
                             class="w-7 h-7 rounded border border-gray-300 dark:border-gray-600 shadow-sm" alt="" loading="lazy">
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
