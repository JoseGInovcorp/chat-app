<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Criar Nova Sala
        </h2>
    </x-slot>

    <div class="py-6">
        <form method="POST" action="{{ route('rooms.store') }}" class="bg-white shadow rounded p-6">
            @csrf

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium">Nome da Sala</label>
                <input type="text" name="name" id="name" 
                       class="mt-1 block w-full border-gray-300 rounded"
                       required>
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="avatar" class="block text-sm font-medium">Avatar (URL opcional)</label>
                <input type="text" name="avatar" id="avatar" 
                       class="mt-1 block w-full border-gray-300 rounded">
            </div>

            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded">
                Criar
            </button>
        </form>
    </div>
</x-app-layout>
