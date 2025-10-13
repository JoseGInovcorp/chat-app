<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\RoomMessageSent;
use App\Events\DirectMessageSent;

/**
 * Controller responsável pela gestão de mensagens (salas e diretas).
 * Permite criar novas mensagens e apagar mensagens existentes.
 */
class MessageController extends Controller
{
    /**
     * Guardar nova mensagem (sala ou direta).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'body'         => 'required|string|max:1000',
            'room_id'      => 'nullable|exists:rooms,id',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        if (!$validated['room_id'] && !$validated['recipient_id']) {
            abort(422, 'É necessário indicar uma sala ou um destinatário.');
        }

        $message = Message::create([
            'sender_id'    => auth()->id(),
            'room_id'      => $validated['room_id'] ?? null,
            'recipient_id' => $validated['recipient_id'] ?? null,
            'body'         => $validated['body'],
        ]);

        // Garante que o sender está carregado
        $message->load('sender');

        // Broadcast para sala
        if ($message->room_id) {
            broadcast(new RoomMessageSent($message))->toOthers();
        }

        // Broadcast para DM
        if ($message->recipient_id) {
            broadcast(new DirectMessageSent($message))->toOthers();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'id'            => $message->id,
                'body'          => $message->body,
                'created_at'    => $message->created_at->toIso8601String(),
                'sender_id'     => $message->sender_id,
                'sender_name'   => $message->sender->name,
                // Melhor prática: usar accessor avatar_url no modelo User
                'sender_avatar' => $message->sender->avatar
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($message->sender->name),
                'room_id'       => $message->room_id,
                'recipient_id'  => $message->recipient_id,
            ]);
        }

        return redirect()->route('rooms.show', $message->room->slug)
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

        return redirect()->route('rooms.show', $message->room->slug)
            ->with('success', 'Mensagem apagada.');
    }
}
