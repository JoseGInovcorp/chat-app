<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">
            Mensagens Diretas
        </h2>
    </x-slot>

    <div class="py-6 animate-fadeIn space-y-4 max-w-3xl mx-auto">
        @foreach($contacts as $contact)
            <a href="{{ route('dm.show', $contact) }}"
               class="flex items-center justify-between px-4 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <div class="flex items-center gap-3">
                    <img src="{{ $contact->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($contact->name) }}"
                         class="w-8 h-8 rounded-full border border-gray-300 dark:border-gray-600 shadow-sm" alt="">
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $contact->name }}</span>
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M9 5l7 7-7 7"/></svg>
            </a>
        @endforeach
    </div>
</x-app-layout>
