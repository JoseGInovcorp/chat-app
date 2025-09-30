<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DirectMessageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Events\TestEvent;

Broadcast::routes([
    'middleware' => ['web', 'auth'], // ✅ inclui 'web' para garantir sessão via cookie
]);

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('rooms.index')
        : view('welcome');
});

// 🚀 Rota de teste para broadcasting
Route::get('/broadcast-test', function () {
    event(new TestEvent('Olá José'));
    return 'Evento disparado!';
});

// ✅ Rota de verificação de sessão
Route::get('/session-check', function () {
    return response()->json([
        'auth_id' => auth()->id(),
        'user' => auth()->user(),
    ]);
})->middleware(['web', 'auth']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Salas de chat
    Route::resource('rooms', RoomController::class)->only(['index', 'show', 'create', 'store']);

    // Convidar utilizadores para sala (apenas admin)
    Route::post('/rooms/{room}/invite', [RoomController::class, 'invite'])
        ->name('rooms.invite')
        ->middleware('can:invite,room');

    // Mensagens em sala
    Route::resource('messages', MessageController::class)->only(['store', 'destroy']);

    // Mensagens diretas (DMs)
    Route::get('/dm/{user}', [DirectMessageController::class, 'show'])->name('dm.show');
    Route::post('/dm/{user}', [DirectMessageController::class, 'store'])->name('dm.store');
});

Route::middleware('auth')->prefix('dm')->group(function () {
    Route::get('{user}', [DirectMessageController::class, 'show'])->name('dm.show');
    Route::post('{user}', [DirectMessageController::class, 'store'])->name('dm.store');
});

require __DIR__ . '/auth.php';
