<div class="p-4 border-t border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-3">
        <img src="{{ Auth::user()->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name) }}"
             class="w-9 h-9 rounded-full border border-gray-300 dark:border-gray-600 shadow-sm" alt="" loading="lazy">
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
