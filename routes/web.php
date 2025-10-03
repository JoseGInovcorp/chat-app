<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DirectMessageController;
use App\Events\TestEvent;

// Broadcasting
Broadcast::routes([
    'middleware' => ['web', 'auth'],
]);

// Página inicial
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('rooms.index')
        : view('welcome');
});

// Teste de broadcasting
Route::get('/broadcast-test', function () {
    event(new TestEvent('Olá José'));
    return 'Evento disparado!';
});

// Verificação de sessão
Route::get('/session-check', function () {
    return response()->json([
        'auth_id' => auth()->id(),
        'user' => auth()->user(),
    ]);
})->middleware(['web', 'auth']);

// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rotas protegidas
Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Salas de chat
    Route::resource('rooms', RoomController::class)->only(['index', 'show', 'create', 'store']);

    // Convites para sala
    Route::get('/rooms/{room}/invite', [RoomController::class, 'inviteForm'])
        ->name('rooms.invite')
        ->middleware('can:invite,room');

    Route::post('/rooms/{room}/invite', [RoomController::class, 'invite'])
        ->name('rooms.invite.submit')
        ->middleware('can:invite,room');

    // Mensagens em sala
    Route::resource('messages', MessageController::class)->only(['store', 'destroy']);

    // Mensagens diretas (DMs)
    Route::prefix('dm')->group(function () {
        Route::get('/', [DirectMessageController::class, 'index'])->name('dm.index');
        Route::get('{user}', [DirectMessageController::class, 'show'])->name('dm.show');
        Route::post('{user}', [DirectMessageController::class, 'store'])->name('dm.store');
    });
});

require __DIR__ . '/auth.php';
