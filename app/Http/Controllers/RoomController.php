<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = auth()->user()->rooms()->orderBy('name')->get();
        return view('rooms.index', compact('rooms'));
    }

    public function show(Room $room)
    {
        // Carregar mensagens da sala, ordenadas
        $messages = $room->messages()
            ->with('sender') // se quiseres mostrar o nome/avatar de quem enviou
            ->orderBy('created_at')
            ->get();

        return view('rooms.show', compact('room', 'messages'));
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'avatar' => 'nullable|string'
        ]);

        $room = Room::create([
            'name' => $data['name'],
            'avatar' => $data['avatar'] ?? null,
            'slug' => Str::slug($data['name']) . '-' . Str::random(6),
        ]);

        // adicionar criador à sala
        $room->users()->syncWithoutDetaching([
            auth()->id() => ['invited_by' => auth()->id(), 'joined_at' => now()]
        ]);

        return redirect()->route('rooms.show', $room);
    }

    public function invite(Request $request, Room $room)
    {
        $data = $request->validate(['user_id' => 'required|exists:users,id']);
        $room->users()->syncWithoutDetaching([
            $data['user_id'] => ['invited_by' => auth()->id(), 'joined_at' => now()]
        ]);
        return back()->with('success', 'Utilizador convidado.');
    }
}
