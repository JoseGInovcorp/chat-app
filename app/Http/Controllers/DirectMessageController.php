<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use App\Events\DirectMessageSent;
use Illuminate\Support\Facades\Log;

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

        // ✅ Marcar como lidas antes de carregar mensagens
        \App\Models\Message::markAsReadFrom($user, $auth);

        $messages = Message::directBetween($auth->id, $user->id)
            ->with('sender:id,name,avatar')
            ->orderByDesc('created_at')   // <- mais recentes primeiro
            ->take(50)                    // <- últimas 50
            ->get()
            ->reverse();                  // <- inverter para mostrar em ordem cronológica

        return view('dm.show', compact('user', 'messages'));
    }

    public function store(Request $request, User $user)
    {
        abort_if(auth()->id() === $user->id, 422, 'Não podes enviar mensagem a ti próprio.');

        $data = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        // Log antes da criação
        Log::debug('DirectMessageController@store:start', [
            'auth_id' => auth()->id(),
            'recipient' => $user->id,
            'body' => mb_strimwidth($data['body'], 0, 200),
        ]);

        $msg = Message::create([
            'sender_id'    => auth()->id(),
            'recipient_id' => $user->id,
            'body'         => $data['body'],
        ]);

        // Log depois da criação
        Log::debug('DirectMessageController@store:created', [
            'message_id' => $msg->id,
            'room_id' => $msg->room_id,
            'recipient_id' => $msg->recipient_id,
        ]);

        // 🔥 Broadcast em tempo real (apenas para o destinatário)
        broadcast(new DirectMessageSent($msg))->toOthers();

        Log::debug('DirectMessageController@store:event_emitted', [
            'message_id' => $msg->id,
            'event' => DirectMessageSent::class,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id'            => $msg->id,
                'body'          => $msg->body,
                'created_at'    => $msg->created_at->format('d/m/Y H:i'),
                'sender_id'     => $msg->sender_id,
                'recipient_id'  => $msg->recipient_id,
                'sender_name'   => $msg->sender->name,
                'sender_avatar' => $msg->sender->avatar
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($msg->sender->name),
            ], 201);
        }

        return back()->with('success', 'Mensagem enviada.');
    }
}
