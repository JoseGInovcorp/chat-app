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
        $rooms = Room::all();
        return view('rooms.index', compact('rooms'));
    }

    public function show(Room $room)
    {
        $this->authorize('view', $room);

        $userId = auth()->id();

        if (!$room->users()->where('user_id', $userId)->exists()) {
            $room->users()->attach($userId, [
                'invited_by'   => $userId,
                'joined_at'    => now(),
                'last_read_at' => now(),
            ]);
        } else {
            $room->users()->updateExistingPivot($userId, [
                'last_read_at' => now(),
            ]);
        }

        $room->load('users');

        // 👉 usar get() em vez de paginate()
        $messages = $room->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return view('rooms.show', compact('room', 'messages'));
    }


    public function create()
    {
        $this->authorize('create', Room::class);
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Room::class);

        $data = $request->validate([
            'name'   => 'required|string|max:120',
            'avatar' => 'nullable|url',
        ]);

        $room = Room::create([
            'name'   => $data['name'],
            'avatar' => $data['avatar'] ?? null,
            'slug'   => Str::slug($data['name']) . '-' . Str::random(6),
        ]);

        $room->users()->attach(auth()->id(), [
            'invited_by'   => auth()->id(),
            'joined_at'    => now(),
            'last_read_at' => now(),
        ]);

        return redirect()->route('rooms.show', $room);
    }

    public function inviteForm(Room $room)
    {
        $this->authorize('invite', $room);

        $users = User::where('id', '!=', auth()->id())->get();
        return view('rooms.invite', compact('room', 'users'));
    }

    public function invite(Request $request, Room $room)
    {
        $this->authorize('invite', $room);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $room->users()->attach($data['user_id'], [
            'invited_by'   => auth()->id(),
            'joined_at'    => now(),
            'last_read_at' => now(),
        ]);

        return back()->with('success', 'Utilizador convidado.');
    }

    public function markActiveRead(Room $room)
    {
        $this->authorize('view', $room);

        $room->users()->updateExistingPivot(auth()->id(), [
            'last_read_at' => now(),
        ]);

        return response()->json(['ok' => true], 200);
    }
}
