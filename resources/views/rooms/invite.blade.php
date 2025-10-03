@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto mt-8 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-4">
        Convidar utilizadores para a sala: {{ $room->name }}
    </h2>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('rooms.invite.submit', $room) }}" class="space-y-4">
        @csrf
        <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Selecionar utilizador
            </label>
            <select name="user_id" id="user_id"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition">
                Convidar
            </button>
        </div>
    </form>
</div>
@endsection
