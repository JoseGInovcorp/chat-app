<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Salas de Chat
        </h2>
    </x-slot>

    <div class="py-6">
        <a href="{{ route('rooms.create') }}" 
           class="px-4 py-2 bg-blue-600 text-white rounded">
            + Nova Sala
        </a>

        <div class="mt-6 bg-white shadow rounded p-4">
            @forelse($rooms as $room)
                <div class="border-b py-2">
                    <a href="{{ route('rooms.show', $room) }}" class="text-blue-600">
                        {{ $room->name }}
                    </a>
                </div>
            @empty
                <p class="text-gray-500">Nenhuma sala criada ainda.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
