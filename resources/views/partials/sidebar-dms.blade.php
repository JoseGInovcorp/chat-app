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
                             class="w-7 h-7 rounded-full border border-gray-300 dark:border-gray-600 shadow-sm" alt="" loading="lazy">
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
