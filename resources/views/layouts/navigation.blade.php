<aside class="w-64 bg-white dark:bg-gray-800 border-r h-screen flex flex-col" role="navigation">
    <!-- Logo -->
    <div class="p-4 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <x-application-logo class="h-8 w-auto text-gray-800 dark:text-gray-200" />
            <span class="font-bold text-gray-700 dark:text-gray-200">Chat App</span>
        </a>
    </div>

    <!-- Conteúdo da Sidebar -->
    <div class="flex-1 overflow-y-auto p-4 space-y-6">
        @include('partials.sidebar-rooms', ['rooms' => $rooms])
        @include('partials.sidebar-dms', ['directContacts' => $directContacts])
    </div>

    <!-- Perfil / Logout -->
    @include('partials.sidebar-profile')
</aside>

@push('scripts')
    @vite('resources/js/navigation.js')
@endpush