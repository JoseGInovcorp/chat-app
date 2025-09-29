<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Guardar nova mensagem (sala ou direta).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
            'room_id' => 'nullable|exists:rooms,id',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        $message = Message::create([
            'sender_id'    => auth()->id(),
            'room_id'      => $validated['room_id'] ?? null,
            'recipient_id' => $validated['recipient_id'] ?? null,
            'body'         => $validated['body'],
        ]);

        if ($message->room_id) {
            event(new \App\Events\RoomMessageSent($message));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'id'            => $message->id,
                'body'          => $message->body,
                'created_at'    => $message->created_at->setTimezone(config('app.timezone'))->format('H:i'),
                'sender_name'   => $message->sender->name,
                'sender_avatar' => $message->sender->avatar
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($message->sender->name),
            ]);
        }

        return redirect()->route('rooms.show', $message->room_id)
            ->with('success', 'Mensagem enviada!');
    }

    /**
     * Apagar mensagem (apenas autor ou admin).
     */
    public function destroy(Message $message)
    {
        if ($message->sender_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Não autorizado.');
        }

        $message->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('rooms.show', $message->room_id)
            ->with('success', 'Mensagem apagada.');
    }
}
