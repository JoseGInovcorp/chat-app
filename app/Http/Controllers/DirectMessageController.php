<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class DirectMessageController extends Controller
{
    public function show(User $user)
    {
        $auth = auth()->user();
        abort_if($auth->id === $user->id, 404);

        $messages = Message::directBetween($auth->id, $user->id)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at')
            ->paginate(50);

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

        // Opcional: broadcasting em tempo real
        event(new \App\Events\DirectMessageSent($msg));

        if ($request->expectsJson()) {
            return response()->json([
                'id'           => $msg->id,
                'body'         => $msg->body,
                'created_at'   => $msg->created_at->format('d/m/Y H:i'),
                'sender_id'    => $msg->sender_id,
                'recipient_id' => $msg->recipient_id,
                'sender_name'  => $msg->sender->name,
                'sender_avatar' => $msg->sender->avatar
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($msg->sender->name),
            ], 201);
        }

        return back()->with('success', 'Mensagem enviada.');
    }
}
