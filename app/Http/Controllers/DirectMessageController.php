<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use App\Events\DirectMessageSent;

/**
 * Controller responsável pela gestão de mensagens diretas (DMs).
 * Permite listar contactos, visualizar conversas e enviar novas mensagens.
 */
class DirectMessageController extends Controller
{
    public function index()
    {
        $contacts = User::where('id', '!=', auth()->id())->get();
        return view('dm.index', compact('contacts'));
    }

    public function show(User $user)
    {
        $auth = auth()->user();
        abort_if($auth->id === $user->id, 404);

        // Marcar como lidas antes de carregar mensagens
        Message::markAsReadFrom($user, $auth);

        $messages = Message::directBetween($auth->id, $user->id)
            ->with('sender:id,name,avatar')
            ->orderByDesc('created_at')
            ->take(50)
            ->get()
            ->reverse();

        return view('dm.show', compact('user', 'messages'));
    }

    public function store(Request $request, User $user)
    {
        abort_if(auth()->id() === $user->id, 422, 'Não podes enviar mensagem a ti próprio.');

        $data = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $msg = Message::create([
            'sender_id'    => auth()->id(),
            'recipient_id' => $user->id,
            'body'         => $data['body'],
        ]);

        // Broadcast em tempo real
        broadcast(new DirectMessageSent($msg))->toOthers();

        if ($request->expectsJson()) {
            return response()->json([
                'id'            => $msg->id,
                'body'          => $msg->body,
                'created_at'    => $msg->created_at->toIso8601String(),
                'sender_id'     => $msg->sender_id,
                'recipient_id'  => $msg->recipient_id,
                'sender_name'   => $msg->sender->name,
                'sender_avatar' => $msg->sender->avatar
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($msg->sender->name),
            ], 201);
        }

        return back()->with('success', 'Mensagem enviada.');
    }

    /**
     * Marca como lidas no servidor todas as mensagens recebidas deste utilizador
     * quando a thread está ativa no cliente.
     */
    public function markActiveRead(User $user)
    {
        $auth = auth()->user();

        if ($auth->id === $user->id) {
            return response()->json(['ok' => true], 200);
        }

        Message::markAsReadFrom($user, $auth);

        return response()->json(['ok' => true], 200);
    }
}
