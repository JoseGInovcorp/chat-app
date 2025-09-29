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
                       class="flex items-center gap-2 px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->is('rooms/'.$room->slug) ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                        <img src="{{ $room->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($room->name) }}"
                             class="w-6 h-6 rounded" alt="">
                        <span class="text-sm text-gray-700 dark:text-gray-200">{{ $room->name }}</span>
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
                       class="flex items-center gap-2 px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->is('dm/'.$contact->id) ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                        <img src="{{ $contact->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($contact->name) }}"
                             class="w-6 h-6 rounded-full" alt="">
                        <span class="text-sm text-gray-700 dark:text-gray-200">{{ $contact->name }}</span>
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
</aside>