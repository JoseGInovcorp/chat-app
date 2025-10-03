<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
            Criar Nova Sala
        </h2>
    </x-slot>

    <div class="py-6 animate-fadeIn">
        <form method="POST" action="{{ route('rooms.store') }}" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-6 max-w-xl mx-auto">
            @csrf

            <div class="space-y-2">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome da Sala</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500"
                       required>
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="avatar" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Avatar (URL opcional)</label>
                <input type="text" name="avatar" id="avatar" value="{{ old('avatar') }}"
                       class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500"
                       oninput="document.getElementById('avatar-preview').src = this.value || 'https://ui-avatars.com/api/?name=Sala';">
                <img id="avatar-preview" src="{{ old('avatar') ?: 'https://ui-avatars.com/api/?name=Sala' }}"
                     onerror="this.src='https://ui-avatars.com/api/?name=Sala';"
                     class="w-16 h-16 rounded border mt-2" alt="Preview do Avatar">
            </div>

            <button type="submit" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 btn-animated">
                <span>Criar</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M12 4v16m8-8H4"/></svg>
            </button>
        </form>
    </div>
</x-app-layout>
