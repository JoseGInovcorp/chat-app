<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
            Salas de Chat
        </h2>
    </x-slot>

    <div class="py-6 animate-fadeIn space-y-6">
        @if(auth()->user()?->isAdmin())
            <a href="{{ route('rooms.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 btn-animated"
               aria-label="Criar nova sala de chat">
                <span>+ Nova Sala</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 4v16m8-8H4"/>
                </svg>
            </a>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 space-y-3">
            @forelse($rooms as $room)
                <a href="{{ route('rooms.show', $room) }}"
                   class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                   aria-label="Entrar na sala {{ $room->name }}">
                    <div class="flex items-center gap-3">
                        <img src="{{ $room->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($room->name) }}"
                             class="w-8 h-8 rounded border border-gray-300 dark:border-gray-600 shadow-sm"
                             alt="Avatar da sala {{ $room->name }}"
                             loading="lazy">
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $room->name }}
                        </span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @empty
                <p class="text-sm text-gray-500">Nenhuma sala criada ainda.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
