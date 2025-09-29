<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\DirectMessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

// Mensagens diretas (DMs)
Route::middleware('auth')->prefix('dm')->group(function () {
    Route::get('{user}', [\App\Http\Controllers\DirectMessageController::class, 'show'])
        ->name('dm.show');
    Route::post('{user}', [\App\Http\Controllers\DirectMessageController::class, 'store'])
        ->name('dm.store');
});

require __DIR__ . '/auth.php';
